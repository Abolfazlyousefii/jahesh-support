<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class FinancialBankAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'bank_name',
        'account_holder',
        'card_number',
        'iban',
        'account_number',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(CustomerPaymentReceipt::class, 'bank_account_id');
    }

    public function maskedCardNumber(): ?string
    {
        if (! $this->card_number) {
            return null;
        }

        $digits = $this->card_number;

        return strlen($digits) === 16
            ? substr($digits, 0, 4).' '.substr($digits, 4, 4).' '.substr($digits, 8, 4).' '.substr($digits, 12, 4)
            : $digits;
    }
}
