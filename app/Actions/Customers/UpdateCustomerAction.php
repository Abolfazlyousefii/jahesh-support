<?php

namespace App\Actions\Customers;

use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class UpdateCustomerAction
{
    public function execute(Customer $customer, array $data): Customer
    {
        return DB::transaction(function () use ($customer, $data) {
            $customer->update(collect($data)->only(['name', 'company_name', 'city', 'address', 'notes', 'is_active'])->all());
            $customer->phones()->delete();

            foreach ($data['phones'] as $index => $phone) {
                $customer->phones()->create([
                    'phone' => $phone['phone'],
                    'is_primary' => $index === (int) $data['primary_phone'],
                ]);
            }

            return $customer;
        });
    }
}
