<?php

namespace App\Enums;

enum LedgerEntryType: string
{
    case Debit = 'debit';
    case Credit = 'credit';

    public function label(): string
    {
        return match ($this) {
            self::Debit => 'بدهکار',
            self::Credit => 'بستانکار',
        };
    }

    public function intent(): string
    {
        return match ($this) {
            self::Debit => 'danger',
            self::Credit => 'success',
        };
    }
}
