<?php

namespace App\Actions\Finance;

use App\Enums\LedgerEntryType;
use App\Enums\PaymentReceiptStatus;
use App\Models\CustomerLedgerEntry;
use App\Models\CustomerPaymentReceipt;
use App\Models\User;
use App\Services\Activity\ActivityLogger;
use App\Services\Notifications\InAppNotifier;
use App\Services\Sms\SmsNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewPaymentReceiptAction
{
    public function __construct(
        private readonly SmsNotifier $sms,
        private readonly InAppNotifier $notifications,
        private readonly ActivityLogger $activity,
    ) {}

    public function approve(CustomerPaymentReceipt $receipt, User $actor): CustomerPaymentReceipt
    {
        $receipt = DB::transaction(function () use ($receipt, $actor) {
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

        $this->activity->record(
            'finance.receipt_approved',
            $receipt,
            $actor,
            'فیش پرداخت مشتری تأیید و سند بستانکار ایجاد شد.',
            ['status' => PaymentReceiptStatus::Pending],
            ['status' => $receipt->status],
            [
                'customer_id' => $receipt->customer_id,
                'amount' => $receipt->amount,
                'tracking_code' => $receipt->tracking_code,
            ],
        );

        $ledgerEntry = CustomerLedgerEntry::query()
            ->where('payment_receipt_id', $receipt->id)
            ->first();

        if ($ledgerEntry !== null) {
            $this->activity->record(
                'finance.ledger_created',
                $ledgerEntry,
                $actor,
                'سند بستانکار به‌صورت خودکار از فیش تأییدشده ایجاد شد.',
                new: $this->activity->snapshot($ledgerEntry, [
                    'customer_id', 'type', 'amount', 'description', 'reference', 'entry_date',
                ]),
                metadata: ['payment_receipt_id' => $receipt->id, 'source' => 'payment_receipt'],
            );
        }

        $this->sms->receiptApproved($receipt);
        $this->notifications->receiptApproved($receipt);

        return $receipt;
    }

    public function reject(CustomerPaymentReceipt $receipt, User $actor, string $reason): CustomerPaymentReceipt
    {
        $receipt = DB::transaction(function () use ($receipt, $actor, $reason) {
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

        $this->activity->record(
            'finance.receipt_rejected',
            $receipt,
            $actor,
            'فیش پرداخت مشتری رد شد.',
            ['status' => PaymentReceiptStatus::Pending, 'rejection_reason' => null],
            ['status' => $receipt->status, 'rejection_reason' => $receipt->rejection_reason],
            ['customer_id' => $receipt->customer_id, 'amount' => $receipt->amount],
        );

        $this->sms->receiptRejected($receipt);
        $this->notifications->receiptRejected($receipt);

        return $receipt;
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
