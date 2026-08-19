<?php

namespace App\Actions\CustomerAuth;

use App\Models\Customer;
use App\Models\CustomerPasswordResetCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class VerifyCustomerPasswordResetAction
{
    public function execute(string $phone, string $code): ?Customer
    {
        return DB::transaction(function () use ($phone, $code) {
            $resetCode = CustomerPasswordResetCode::query()
                ->where('phone', $phone)
                ->whereNull('consumed_at')
                ->where('expires_at', '>', now())
                ->where('attempts', '<', (int) config('jahesh.password_reset.max_attempts', 5))
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($resetCode === null) {
                return null;
            }

            if (! Hash::check($code, $resetCode->code_hash)) {
                $resetCode->increment('attempts');

                return null;
            }

            $customer = Customer::query()
                ->active()
                ->whereKey($resetCode->customer_id)
                ->whereHas('phones', fn ($query) => $query->where('phone', $phone))
                ->first();

            if ($customer === null) {
                $resetCode->update(['consumed_at' => now()]);

                return null;
            }

            $resetCode->update([
                'verified_at' => now(),
                'consumed_at' => now(),
            ]);

            return $customer;
        });
    }
}
