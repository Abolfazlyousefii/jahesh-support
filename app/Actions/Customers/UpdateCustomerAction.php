<?php

namespace App\Actions\Customers;

use App\Models\Customer;
use App\Models\User;
use App\Services\Activity\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UpdateCustomerAction
{
    private const AUDITED_FIELDS = ['name', 'company_name', 'city', 'address', 'notes', 'is_active'];

    public function __construct(private readonly ActivityLogger $activity) {}

    public function execute(Customer $customer, array $data, ?User $actor = null): Customer
    {
        $customer->load('phones');
        $before = $this->snapshot($customer);
        $passwordChanged = filled($data['password'] ?? null);

        $customer = DB::transaction(function () use ($customer, $data) {
            $customerData = collect($data)->only(self::AUDITED_FIELDS)->all();

            if (filled($data['password'] ?? null)) {
                $customerData['password'] = Hash::make((string) $data['password']);
                $customerData['password_changed_at'] = now();
            }

            $customer->update($customerData);
            $customer->phones()->delete();

            foreach ($data['phones'] as $index => $phone) {
                $customer->phones()->create([
                    'phone' => $phone['phone'],
                    'is_primary' => $index === (int) $data['primary_phone'],
                ]);
            }

            return $customer->refresh()->load('phones');
        });

        $changes = $this->activity->changed($before, $this->snapshot($customer));

        if ($changes['old'] !== [] || $changes['new'] !== []) {
            $this->activity->record(
                'customer.updated',
                $customer,
                $actor,
                'اطلاعات مشتری ویرایش شد.',
                $changes['old'],
                $changes['new'],
            );
        }

        if ($passwordChanged) {
            $this->activity->record(
                'customer.password_changed_by_admin',
                $customer,
                $actor,
                'رمز ورود مشتری توسط مدیر تغییر کرد.',
            );
        }

        return $customer;
    }

    /** @return array<string,mixed> */
    private function snapshot(Customer $customer): array
    {
        return [
            ...$this->activity->snapshot($customer, self::AUDITED_FIELDS),
            'phones' => $customer->phones->pluck('phone')->values()->all(),
        ];
    }
}
