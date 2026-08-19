<?php

namespace App\Actions\CustomerAuth;

use App\Contracts\PasswordResetOtpSender;
use App\Models\CustomerPasswordResetCode;
use App\Models\CustomerPhone;
use Illuminate\Support\Facades\Hash;

class RequestCustomerPasswordResetAction
{
    public function __construct(private readonly PasswordResetOtpSender $sender) {}

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

        $cooldown = (int) config('jahesh.password_reset.cooldown_seconds', 60);

        if (CustomerPasswordResetCode::query()
            ->where('phone', $phone)
            ->where('created_at', '>', now()->subSeconds($cooldown))
            ->exists()) {
            return;
        }

        CustomerPasswordResetCode::query()
            ->where('phone', $phone)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        $code = (string) random_int(100000, 999999);

        CustomerPasswordResetCode::query()->create([
            'customer_id' => $phoneRecord->customer_id,
            'phone' => $phone,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes((int) config('jahesh.password_reset.expire_minutes', 5)),
            'attempts' => 0,
            'requested_ip' => $ip,
        ]);

        $this->sender->send($phone, $code);
    }
}
