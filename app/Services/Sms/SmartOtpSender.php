<?php

namespace App\Services\Sms;

use App\Contracts\OtpSender;
use App\Models\SmsLog;
use App\Models\SmsSetting;
use App\Services\Otp\LogOtpSender;
use RuntimeException;

final class SmartOtpSender implements OtpSender
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

            throw new RuntimeException('ارسال پیامک OTP در تنظیمات غیرفعال است.');
        }

        $log = $this->sms->sendNow('customer_otp', $phone, [$code]);

        if ($log->status !== SmsLog::STATUS_SENT) {
            throw new RuntimeException($log->error ?: 'ارسال کد ورود انجام نشد.');
        }
    }
}
