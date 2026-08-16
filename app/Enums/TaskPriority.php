<?php

namespace App\Enums;

enum TaskPriority: string
{
    case Normal = 'normal';
    case Important = 'important';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::Normal => 'عادی',
            self::Important => 'مهم',
            self::Urgent => 'فوری',
        };
    }

    public function intent(): string
    {
        return match ($this) {
            self::Normal => 'neutral',
            self::Important => 'warning',
            self::Urgent => 'danger',
        };
    }
}
