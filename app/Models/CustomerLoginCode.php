<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Hidden(['code_hash'])]
class CustomerLoginCode extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'customer_id', 'phone', 'code_hash', 'expires_at', 'attempts', 'consumed_at', 'requested_ip',
    ];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'consumed_at' => 'datetime'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
