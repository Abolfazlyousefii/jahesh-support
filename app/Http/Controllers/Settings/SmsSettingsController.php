<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\TestSmsPatternRequest;
use App\Http\Requests\Settings\UpdateSmsSettingsRequest;
use App\Models\SmsLog;
use App\Models\SmsPattern;
use App\Models\SmsSetting;
use App\Models\User;
use App\Services\Activity\ActivityLogger;
use App\Services\Sms\SmsPatternCatalog;
use App\Services\Sms\SmsService;
use App\Services\Settings\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SmsSettingsController extends Controller
{
    public function index(Request $request): View
    {
        SmsPatternCatalog::ensureStored();

        $status = in_array($request->string('status')->toString(), [
            SmsLog::STATUS_QUEUED,
            SmsLog::STATUS_SENT,
            SmsLog::STATUS_FAILED,
            SmsLog::STATUS_SKIPPED,
        ], true) ? $request->string('status')->toString() : null;

        $logs = SmsLog::query()
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(app(SettingsService::class)->paginationPerPage())
            ->withQueryString();

        return view('settings.sms', [
            'setting' => SmsSetting::current(),
            'patterns' => SmsPatternCatalog::stored(),
            'definitions' => SmsPatternCatalog::definitions(),
            'users' => User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'phone']),
            'logs' => $logs,
            'status' => $status,
        ]);
    }

    public function update(UpdateSmsSettingsRequest $request, ActivityLogger $activity): RedirectResponse
    {
        SmsPatternCatalog::ensureStored();

        $setting = SmsSetting::current();
        $before = $this->auditSnapshot($setting);
        $data = $request->validated();
        $credentialsChanged = filled($data['webservice_password'] ?? null);

        $settingData = [
            'enabled' => $data['enabled'],
            'provider' => 'melipayamak',
            'webservice_username' => $data['webservice_username'] ?? null,
            'internal_recipient_user_ids' => $data['internal_recipient_user_ids'] ?? [],
        ];

        if ($credentialsChanged) {
            $settingData['webservice_password'] = $data['webservice_password'];
        }

        $setting->update($settingData);

        foreach (SmsPatternCatalog::definitions() as $key => $definition) {
            $payload = (array) data_get($data, 'patterns.'.$key, []);
            SmsPattern::query()->updateOrCreate(
                ['key' => $key],
                [
                    'title' => $definition['title'],
                    'enabled' => filter_var($payload['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'body_id' => filled($payload['body_id'] ?? null) ? (int) $payload['body_id'] : null,
                ],
            );
        }

        $setting->refresh();
        $after = $this->auditSnapshot($setting);
        $changes = $activity->changed($before, $after);

        if ($changes['old'] !== [] || $changes['new'] !== [] || $credentialsChanged) {
            $activity->record(
                'settings.sms_updated',
                $setting,
                $request->user(),
                'تنظیمات پیامک و الگوهای ملی پیامک تغییر کرد.',
                $changes['old'],
                $changes['new'],
                ['credentials_changed' => $credentialsChanged],
            );
        }

        return back()->with('success', 'تنظیمات پیامک ذخیره شد.');
    }

    public function testConnection(SmsService $sms): RedirectResponse
    {
        $result = $sms->testConnection();

        if (! $result['ok']) {
            return back()->withErrors(['sms_connection' => $result['error']]);
        }

        $credit = $result['credit'] !== null ? number_format($result['credit'], 2) : 'نامشخص';

        return back()->with('success', "اتصال به ملی پیامک برقرار است. اعتبار گزارش‌شده: {$credit}");
    }

    public function testPattern(TestSmsPatternRequest $request, SmsService $sms): RedirectResponse
    {
        $key = $request->validated('pattern_key');
        $log = $sms->sendNow(
            $key,
            $request->validated('phone'),
            SmsPatternCatalog::sampleValues($key),
        );

        if ($log->status !== SmsLog::STATUS_SENT) {
            return back()->withErrors(['sms_test' => $log->error ?: 'ارسال تست ناموفق بود.']);
        }

        return back()->with('success', 'پیامک تست با موفقیت به صف/وب‌سرویس ارسال شد.');
    }

    /** @return array<string,mixed> */
    private function auditSnapshot(SmsSetting $setting): array
    {
        return [
            'enabled' => $setting->enabled,
            'provider' => $setting->provider,
            'webservice_username' => $setting->webservice_username,
            'internal_recipient_user_ids' => collect($setting->internal_recipient_user_ids ?? [])
                ->map(fn ($id) => (int) $id)
                ->sort()
                ->values()
                ->all(),
            'patterns' => SmsPattern::query()
                ->orderBy('key')
                ->get(['key', 'enabled', 'body_id'])
                ->mapWithKeys(fn (SmsPattern $pattern) => [
                    $pattern->key => [
                        'enabled' => $pattern->enabled,
                        'body_id' => $pattern->body_id,
                    ],
                ])
                ->all(),
        ];
    }
}
