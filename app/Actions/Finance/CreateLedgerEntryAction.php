<?php

namespace App\Actions\Finance;

use App\Models\Customer;
use App\Models\CustomerLedgerEntry;
use App\Models\User;
use App\Services\Activity\ActivityLogger;

class CreateLedgerEntryAction
{
    public function __construct(private readonly ActivityLogger $activity) {}

    /** @param array<string,mixed> $data */
    public function execute(Customer $customer, array $data, User $actor): CustomerLedgerEntry
    {
        $entry = CustomerLedgerEntry::query()->create([
            'customer_id' => $customer->id,
            'type' => $data['type'],
            'amount' => $data['amount'],
            'description' => $data['description'],
            'reference' => $data['reference'] ?? null,
            'entry_date' => $data['entry_date'],
            'source' => 'manual',
            'created_by' => $actor->id,
        ]);

        $this->activity->record(
            'finance.ledger_created',
            $entry,
            $actor,
            'سند مالی جدید برای مشتری ثبت شد.',
            new: $this->activity->snapshot($entry, [
                'customer_id', 'type', 'amount', 'description', 'reference', 'entry_date',
            ]),
            metadata: ['customer_name' => $customer->name],
        );

        return $entry;
    }
}
