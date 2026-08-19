<?php

namespace App\Actions\Finance;

use App\Models\CustomerLedgerEntry;
use App\Models\User;
use App\Services\Activity\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VoidLedgerEntryAction
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function execute(CustomerLedgerEntry $entry, User $actor, string $reason): CustomerLedgerEntry
    {
        $entry = DB::transaction(function () use ($entry, $actor, $reason) {
            $locked = CustomerLedgerEntry::query()->lockForUpdate()->findOrFail($entry->id);

            if ($locked->voided_at !== null) {
                throw ValidationException::withMessages([
                    'void_reason' => 'این سند قبلاً ابطال شده است.',
                ]);
            }

            $locked->update([
                'voided_at' => now(),
                'voided_by' => $actor->id,
                'void_reason' => $reason,
            ]);

            return $locked->refresh();
        });

        $this->activity->record(
            'finance.ledger_voided',
            $entry,
            $actor,
            'سند مالی ابطال شد.',
            old: ['voided_at' => null, 'void_reason' => null],
            new: ['voided_at' => $entry->voided_at, 'void_reason' => $entry->void_reason],
            metadata: [
                'customer_id' => $entry->customer_id,
                'amount' => $entry->amount,
                'type' => $entry->type,
            ],
        );

        return $entry;
    }
}
