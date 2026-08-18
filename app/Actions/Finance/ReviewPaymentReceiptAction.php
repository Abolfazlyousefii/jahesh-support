<?php

namespace App\Actions\Finance;

use App\Enums\LedgerEntryType;
use App\Enums\PaymentReceiptStatus;
use App\Models\CustomerLedgerEntry;
use App\Models\CustomerPaymentReceipt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewPaymentReceiptAction
{
    public function approve(CustomerPaymentReceipt $receipt, User $actor): CustomerPaymentReceipt
    {
        return DB::transaction(function () use ($receipt, $actor) {
            $locked = CustomerPaymentReceipt::query()->lockForUpdate()->findOrFail($receipt->id);

            $this->ensurePending($locked);

            CustomerLedgerEntry::query()->firstOrCreate(
                ['payment_receipt_id' => $locked->id],
                [
                    'customer_id' => $locked->customer_id,
                    'type' => LedgerEntryType::Credit,
                    'amount' => $locked->amount,
                    'description' => 'پرداخت کارت به کارت تأییدشده',
                    'reference' => $locked->tracking_code,
                    'entry_date' => $locked->paid_at,
                    'source' => 'payment_receipt',
                    'created_by' => $actor->id,
                ],
            );

            $locked->update([
                'status' => PaymentReceiptStatus::Approved,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ]);

            return $locked->refresh();
        });
    }

    public function reject(CustomerPaymentReceipt $receipt, User $actor, string $reason): CustomerPaymentReceipt
    {
        return DB::transaction(function () use ($receipt, $actor, $reason) {
            $locked = CustomerPaymentReceipt::query()->lockForUpdate()->findOrFail($receipt->id);

            $this->ensurePending($locked);

            $locked->update([
                'status' => PaymentReceiptStatus::Rejected,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
            ]);

            return $locked->refresh();
        });
    }

    private function ensurePending(CustomerPaymentReceipt $receipt): void
    {
        if ($receipt->status !== PaymentReceiptStatus::Pending) {
            throw ValidationException::withMessages([
                'receipt' => 'این فیش قبلاً بررسی شده و امکان بررسی دوباره آن وجود ندارد.',
            ]);
        }
    }
}
