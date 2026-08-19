<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateGeneralSettingsRequest;
use App\Models\GeneralSetting;
use App\Services\Activity\ActivityLogger;
use App\Services\Settings\SettingsService;
use App\Support\GeneralSettingsCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GeneralSettingsController extends Controller
{
    public function index(SettingsService $settings): View
    {
        return view('settings.general', [
            'settings' => $settings->all(),
        ]);
    }

    public function update(
        UpdateGeneralSettingsRequest $request,
        SettingsService $settings,
        ActivityLogger $activity,
    ): RedirectResponse {
        $before = $settings->all();
        $data = $request->validated();

        $settings->update([
            'general.company_name' => $data['company_name'],
            'general.app_name' => $data['app_name'],
            'general.support_phone' => $data['support_phone'] ?? '',
            'general.support_hours' => $data['support_hours'] ?? '',
            'general.support_text' => $data['support_text'] ?? '',
            'general.pagination_per_page' => $data['pagination_per_page'],
            'portal.title' => $data['portal_title'],
            'portal.welcome_text' => $data['portal_welcome_text'],
            'portal.show_support_phone' => $data['portal_show_support_phone'],
            'portal.show_support_hours' => $data['portal_show_support_hours'],
            'portal.active_ticket_limit' => $data['portal_active_ticket_limit'],
        ]);

        $after = $settings->all();
        $changes = $activity->changed(
            $this->auditSnapshot($before),
            $this->auditSnapshot($after),
        );

        if ($changes['old'] !== [] || $changes['new'] !== []) {
            $subject = GeneralSetting::query()
                ->where('key', 'general.company_name')
                ->first();

            $activity->record(
                'settings.general_updated',
                $subject,
                $request->user(),
                'تنظیمات عمومی نرم‌افزار تغییر کرد.',
                $changes['old'],
                $changes['new'],
            );
        }

        return back()->with('success', 'تنظیمات عمومی با موفقیت ذخیره شد.');
    }

    /** @param array<string,mixed> $values
     *  @return array<string,mixed>
     */
    private function auditSnapshot(array $values): array
    {
        $definitions = GeneralSettingsCatalog::definitions();

        return collect($definitions)
            ->mapWithKeys(fn (array $definition, string $key) => [
                str_replace('.', '_', $key) => $values[$key] ?? $definition['default'],
            ])
            ->all();
    }
}
