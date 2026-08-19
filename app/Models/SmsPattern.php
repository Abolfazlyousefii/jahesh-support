<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsPattern extends Model
{
    protected $fillable = ['key', 'title', 'body_id', 'enabled'];

    protected function casts(): array
    {
        return [
            'body_id' => 'integer',
            'enabled' => 'boolean',
        ];
    }
}
