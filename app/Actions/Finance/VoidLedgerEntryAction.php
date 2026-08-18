<?php

namespace App\Actions\Finance;

use App\Models\CustomerLedgerEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VoidLedgerEntryAction
{
    public function execute(CustomerLedgerEntry $entry, User $actor, string $reason): CustomerLedgerEntry
    {
        return DB::transaction(function () use ($entry, $actor, $reason) {
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
    }
}
