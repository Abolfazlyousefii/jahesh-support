<?php

namespace App\Actions\CustomerAuth;

use App\Models\Customer;
use App\Models\CustomerPasswordResetCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ResetCustomerPasswordAction
{
    public function execute(Customer $customer, string $phone, string $password): void
    {
        DB::transaction(function () use ($customer, $phone, $password) {
            $lockedCustomer = Customer::query()
                ->active()
                ->whereKey($customer->getKey())
                ->whereHas('phones', fn ($query) => $query->where('phone', $phone))
                ->lockForUpdate()
                ->firstOrFail();

            $lockedCustomer->forceFill([
                'password' => Hash::make($password),
                'password_changed_at' => now(),
            ])->save();

            CustomerPasswordResetCode::query()
                ->where('customer_id', $lockedCustomer->id)
                ->whereNull('consumed_at')
                ->update(['consumed_at' => now()]);
        });
    }
}
