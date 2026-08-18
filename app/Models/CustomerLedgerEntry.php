<?php

namespace App\Models;

use App\Enums\LedgerEntryType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerLedgerEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'type',
        'amount',
        'description',
        'reference',
        'entry_date',
        'source',
        'payment_receipt_id',
        'created_by',
        'voided_at',
        'voided_by',
        'void_reason',
    ];

    protected function casts(): array
    {
        return [
            'type' => LedgerEntryType::class,
            'amount' => 'integer',
            'entry_date' => 'date',
            'voided_at' => 'datetime',
        ];
    }

    public function scopeEffective(Builder $query): Builder
    {
        return $query->whereNull('voided_at');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function voider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by')->withTrashed();
    }

    public function paymentReceipt(): BelongsTo
    {
        return $this->belongsTo(CustomerPaymentReceipt::class, 'payment_receipt_id');
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }
}
