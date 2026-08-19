<?php

namespace App\Services\Sms;

use App\Contracts\SmsGateway;
use App\Jobs\SendPatternSmsJob;
use App\Models\SmsLog;
use App\Models\SmsPattern;
use App\Models\SmsSetting;
use App\Support\PhoneNormalizer;

final class SmsService
{
    public function queue(
        string $patternKey,
        string $recipient,
        array $parameters,
        ?string $relatedType = null,
        ?int $relatedId = null,
    ): ?SmsLog {
        $setting = SmsSetting::current();

        if (! $setting->enabled) {
            return null;
        }

        $pattern = $this->pattern($patternKey);
        $phone = PhoneNormalizer::normalize($recipient);

        if (! $setting->hasCredentials() || ! $pattern->enabled || ! $pattern->body_id || ! preg_match('/^09\d{9}$/', $phone)) {
            return SmsLog::query()->create([
                'recipient' => $phone ?: $recipient,
                'pattern_key' => $patternKey,
                'related_type' => $relatedType,
                'related_id' => $relatedId,
                'status' => SmsLog::STATUS_SKIPPED,
                'error' => $this->skipReason($setting, $pattern, $phone),
            ]);
        }

        $log = SmsLog::query()->create([
            'recipient' => $phone,
            'pattern_key' => $patternKey,
            'related_type' => $relatedType,
            'related_id' => $relatedId,
            'status' => SmsLog::STATUS_QUEUED,
        ]);

        SendPatternSmsJob::dispatch($log->id, array_values($parameters))->afterCommit();

        return $log;
    }

    public function sendNow(
        string $patternKey,
        string $recipient,
        array $parameters,
        ?string $relatedType = null,
        ?int $relatedId = null,
    ): SmsLog {
        $setting = SmsSetting::current();
        $pattern = $this->pattern($patternKey);
        $phone = PhoneNormalizer::normalize($recipient);

        $log = SmsLog::query()->create([
            'recipient' => $phone ?: $recipient,
            'pattern_key' => $patternKey,
            'related_type' => $relatedType,
            'related_id' => $relatedId,
            'status' => SmsLog::STATUS_QUEUED,
        ]);

        if (! $setting->enabled || ! $setting->hasCredentials() || ! $pattern->enabled || ! $pattern->body_id || ! preg_match('/^09\d{9}$/', $phone)) {
            $log->update([
                'status' => SmsLog::STATUS_SKIPPED,
                'error' => $this->skipReason($setting, $pattern, $phone),
            ]);

            return $log->refresh();
        }

        return $this->deliver($log, $parameters, $setting, $pattern);
    }

    public function deliverQueued(int $logId, array $parameters): void
    {
        $log = SmsLog::query()->find($logId);

        if ($log === null || $log->status !== SmsLog::STATUS_QUEUED) {
            return;
        }

        $setting = SmsSetting::current();
        $pattern = $this->pattern($log->pattern_key);

        if (! $setting->enabled || ! $setting->hasCredentials() || ! $pattern->enabled || ! $pattern->body_id) {
            $log->update([
                'status' => SmsLog::STATUS_SKIPPED,
                'error' => $this->skipReason($setting, $pattern, $log->recipient),
            ]);

            return;
        }

        $this->deliver($log, $parameters, $setting, $pattern);
    }

    /** @return array{ok:bool,credit:?float,error:?string} */
    public function testConnection(): array
    {
        $setting = SmsSetting::current();

        if (! $setting->hasCredentials()) {
            return ['ok' => false, 'credit' => null, 'error' => 'نام کاربری و رمز وب‌سرویس را ابتدا ذخیره کنید.'];
        }

        return $this->gateway($setting)->testConnection();
    }

    private function deliver(SmsLog $log, array $parameters, SmsSetting $setting, SmsPattern $pattern): SmsLog
    {
        $result = $this->gateway($setting)->sendPattern(
            $log->recipient,
            (int) $pattern->body_id,
            $parameters,
        );

        $log->update($result->success
            ? [
                'status' => SmsLog::STATUS_SENT,
                'provider_message_id' => $result->messageId,
                'error' => null,
                'sent_at' => now(),
            ]
            : [
                'status' => SmsLog::STATUS_FAILED,
                'error' => $result->error,
            ]);

        return $log->refresh();
    }

    private function gateway(SmsSetting $setting): SmsGateway
    {
        return new MelipayamakGateway(
            (string) $setting->webservice_username,
            (string) $setting->webservice_password,
        );
    }

    private function pattern(string $key): SmsPattern
    {
        SmsPatternCatalog::ensureStored($key);

        return SmsPattern::query()->where('key', $key)->firstOrFail();
    }

    private function skipReason(SmsSetting $setting, SmsPattern $pattern, string $phone): string
    {
        return match (true) {
            ! $setting->enabled => 'ارسال پیامک در تنظیمات غیرفعال است.',
            ! $setting->hasCredentials() => 'اطلاعات وب‌سرویس کامل نیست.',
            ! $pattern->enabled => 'الگوی پیامک غیرفعال است.',
            ! $pattern->body_id => 'Body ID الگو ثبت نشده است.',
            ! preg_match('/^09\d{9}$/', $phone) => 'شماره موبایل گیرنده معتبر نیست.',
            default => 'ارسال انجام نشد.',
        };
    }
}
