<?php

namespace App\Services\Finance;

use App\Enums\LedgerEntryType;
use App\Enums\PaymentReceiptStatus;
use App\Models\Customer;

class CustomerFinanceService
{
    /** @return array{debit:int,credit:int,balance:int,balance_abs:int,balance_kind:string,pending_receipts:int,pending_amount:int} */
    public function summary(Customer $customer): array
    {
        $base = $customer->ledgerEntries()->effective();
        $debit = (int) (clone $base)->where('type', LedgerEntryType::Debit)->sum('amount');
        $credit = (int) (clone $base)->where('type', LedgerEntryType::Credit)->sum('amount');
        $balance = $debit - $credit;

        $pending = $customer->paymentReceipts()->where('status', PaymentReceiptStatus::Pending);

        return [
            'debit' => $debit,
            'credit' => $credit,
            'balance' => $balance,
            'balance_abs' => abs($balance),
            'balance_kind' => $balance > 0 ? 'debit' : ($balance < 0 ? 'credit' : 'settled'),
            'pending_receipts' => (int) (clone $pending)->count(),
            'pending_amount' => (int) (clone $pending)->sum('amount'),
        ];
    }
}
