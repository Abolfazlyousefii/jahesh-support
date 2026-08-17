<?php

namespace App\Actions\CustomerAuth;

use App\Models\Customer;
use App\Models\CustomerLoginCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class VerifyCustomerOtpAction
{
    public function execute(string $phone, string $code): ?Customer
    {
        return DB::transaction(function () use ($phone, $code) {
            $loginCode = CustomerLoginCode::query()
                ->where('phone', $phone)
                ->whereNull('consumed_at')
                ->where('expires_at', '>', now())
                ->where('attempts', '<', (int) config('jahesh.otp.max_attempts', 5))
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($loginCode === null) {
                return null;
            }

            if (! Hash::check($code, $loginCode->code_hash)) {
                $loginCode->increment('attempts');

                return null;
            }

            $customer = Customer::query()->active()->find($loginCode->customer_id);
            if ($customer === null) {
                $loginCode->update(['consumed_at' => now()]);

                return null;
            }

            $loginCode->update(['consumed_at' => now()]);

            return $customer;
        });
    }
}
