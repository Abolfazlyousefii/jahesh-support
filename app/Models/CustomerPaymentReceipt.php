<?php

namespace App\Models;

use App\Enums\PaymentReceiptStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CustomerPaymentReceipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'bank_account_id',
        'amount',
        'paid_at',
        'tracking_code',
        'receipt_path',
        'original_name',
        'mime_type',
        'customer_note',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'paid_at' => 'date',
            'status' => PaymentReceiptStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', PaymentReceiptStatus::Pending);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialBankAccount::class, 'bank_account_id')->withTrashed();
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by')->withTrashed();
    }

    public function ledgerEntry(): HasOne
    {
        return $this->hasOne(CustomerLedgerEntry::class, 'payment_receipt_id');
    }
}
