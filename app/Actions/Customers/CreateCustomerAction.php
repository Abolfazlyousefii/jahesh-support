<?php

namespace App\Actions\Customers;

use App\Models\Customer;
use App\Models\User;
use App\Services\Activity\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateCustomerAction
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function execute(array $data, ?User $actor = null): Customer
    {
        $customer = DB::transaction(function () use ($data) {
            $customer = Customer::query()->create($this->customerData($data));
            $this->storePhones($customer, $data['phones'], (int) $data['primary_phone']);

            return $customer;
        });

        $customer->load('phones');

        $this->activity->record(
            'customer.created',
            $customer,
            $actor,
            'مشتری جدید در سیستم ثبت شد.',
            new: [
                'name' => $customer->name,
                'company_name' => $customer->company_name,
                'city' => $customer->city,
                'address' => $customer->address,
                'notes' => $customer->notes,
                'is_active' => $customer->is_active,
                'phones' => $customer->phones->pluck('phone')->values()->all(),
            ],
        );

        if (filled($data['password'] ?? null)) {
            $this->activity->record(
                'customer.password_changed_by_admin',
                $customer,
                $actor,
                'برای مشتری هنگام ایجاد حساب، رمز ورود تعیین شد.',
            );
        }

        return $customer;
    }

    private function storePhones(Customer $customer, array $phones, int $primary): void
    {
        foreach ($phones as $index => $phone) {
            $customer->phones()->create([
                'phone' => $phone['phone'],
                'is_primary' => $index === $primary,
            ]);
        }
    }

    private function customerData(array $data): array
    {
        $customerData = collect($data)->only(['name', 'company_name', 'city', 'address', 'notes', 'is_active'])->all();

        if (filled($data['password'] ?? null)) {
            $customerData['password'] = Hash::make((string) $data['password']);
            $customerData['password_changed_at'] = now();
        }

        return $customerData;
    }
}
