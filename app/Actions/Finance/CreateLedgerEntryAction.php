<?php

namespace App\Actions\Finance;

use App\Models\Customer;
use App\Models\CustomerLedgerEntry;
use App\Models\User;

class CreateLedgerEntryAction
{
    /** @param array<string,mixed> $data */
    public function execute(Customer $customer, array $data, User $actor): CustomerLedgerEntry
    {
        return CustomerLedgerEntry::query()->create([
            'customer_id' => $customer->id,
            'type' => $data['type'],
            'amount' => $data['amount'],
            'description' => $data['description'],
            'reference' => $data['reference'] ?? null,
            'entry_date' => $data['entry_date'],
            'source' => 'manual',
            'created_by' => $actor->id,
        ]);
    }
}
