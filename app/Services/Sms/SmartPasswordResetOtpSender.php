<?php

namespace App\Services\Sms;

use App\Contracts\PasswordResetOtpSender;
use App\Models\SmsLog;
use App\Models\SmsSetting;
use App\Services\Otp\LogOtpSender;
use RuntimeException;

final class SmartPasswordResetOtpSender implements PasswordResetOtpSender
{
    public function __construct(
        private readonly SmsService $sms,
        private readonly LogOtpSender $logSender,
    ) {}

    public function send(string $phone, string $code): void
    {
        $driver = (string) config('jahesh.otp.driver', 'auto');

        if ($driver === 'log') {
            $this->logSender->send($phone, $code);

            return;
        }

        $setting = SmsSetting::current();

        if (! $setting->enabled) {
            if (app()->environment('local', 'testing')) {
                $this->logSender->send($phone, $code);

                return;
            }

            throw new RuntimeException('ارسال پیامک بازیابی رمز عبور در تنظیمات غیرفعال است.');
        }

        $log = $this->sms->sendNow('customer_password_reset_otp', $phone, [$code]);

        if ($log->status !== SmsLog::STATUS_SENT) {
            throw new RuntimeException($log->error ?: 'ارسال کد بازیابی رمز عبور انجام نشد.');
        }
    }
}
