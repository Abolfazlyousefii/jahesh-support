<?php

namespace App\Services\Otp;

use App\Contracts\OtpSender;
use Illuminate\Support\Facades\Log;

class LogOtpSender implements OtpSender
{
    public function send(string $phone, string $code): void
    {
        Log::info('Customer portal OTP', ['phone' => $phone, 'code' => $code]);
    }
}
