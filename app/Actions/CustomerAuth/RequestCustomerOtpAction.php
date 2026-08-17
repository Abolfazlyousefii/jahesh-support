<?php

namespace App\Actions\CustomerAuth;

use App\Contracts\OtpSender;
use App\Models\CustomerLoginCode;
use App\Models\CustomerPhone;
use Illuminate\Support\Facades\Hash;

class RequestCustomerOtpAction
{
    public function __construct(private readonly OtpSender $sender) {}

    public function execute(string $phone, ?string $ip): void
    {
        $phoneRecord = CustomerPhone::query()
            ->where('phone', $phone)
            ->whereHas('customer', fn ($query) => $query->active())
            ->with('customer')
            ->first();

        if ($phoneRecord === null) {
            return;
        }

        $cooldown = (int) config('jahesh.otp.cooldown_seconds', 60);
        $recentlyRequested = CustomerLoginCode::query()
            ->where('phone', $phone)
            ->where('created_at', '>', now()->subSeconds($cooldown))
            ->exists();

        if ($recentlyRequested) {
            return;
        }

        CustomerLoginCode::query()
            ->where('phone', $phone)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        $code = (string) random_int(100000, 999999);
        CustomerLoginCode::query()->create([
            'customer_id' => $phoneRecord->customer_id,
            'phone' => $phone,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes((int) config('jahesh.otp.expire_minutes', 5)),
            'attempts' => 0,
            'requested_ip' => $ip,
        ]);

        $this->sender->send($phone, $code);
    }
}
