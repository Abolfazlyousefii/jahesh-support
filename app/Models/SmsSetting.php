<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsSetting extends Model
{
    protected $fillable = [
        'enabled',
        'provider',
        'webservice_username',
        'webservice_password',
        'internal_recipient_user_ids',
    ];

    protected $hidden = ['webservice_password'];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'webservice_password' => 'encrypted',
            'internal_recipient_user_ids' => 'array',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            [
                'enabled' => false,
                'provider' => 'melipayamak',
                'internal_recipient_user_ids' => [],
            ],
        );
    }

    public function hasCredentials(): bool
    {
        return filled($this->webservice_username) && filled($this->webservice_password);
    }
}
