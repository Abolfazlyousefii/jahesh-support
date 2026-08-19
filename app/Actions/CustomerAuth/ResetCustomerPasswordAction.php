<?php

namespace App\Actions\CustomerAuth;

use App\Models\Customer;
use App\Models\CustomerPasswordResetCode;
use App\Services\Activity\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ResetCustomerPasswordAction
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function execute(Customer $customer, string $phone, string $password): void
    {
        $resetCustomer = DB::transaction(function () use ($customer, $phone, $password) {
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

            return $lockedCustomer->refresh();
        });

        $this->activity->record(
            'customer.password_reset',
            $resetCustomer,
            $resetCustomer,
            'رمز عبور مشتری پس از تأیید کد بازیابی تغییر کرد.',
            metadata: ['phone' => $phone],
        );
    }
}
