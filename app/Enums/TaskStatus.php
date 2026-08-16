<?php

namespace App\Enums;

enum TaskStatus: string
{
    case New = 'new';
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Review = 'review';
    case Completed = 'completed';
    case Paused = 'paused';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::New => 'جدید',
            self::Pending => 'در انتظار شروع',
            self::InProgress => 'در حال انجام',
            self::Review => 'نیازمند بررسی',
            self::Completed => 'تکمیل شده',
            self::Paused => 'متوقف',
            self::Cancelled => 'لغو شده',
        };
    }

    public function intent(): string
    {
        return match ($this) {
            self::Completed => 'success',
            self::InProgress => 'info',
            self::Review, self::Paused => 'warning',
            self::Cancelled => 'danger',
            default => 'neutral',
        };
    }

    public function isClosed(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled], true);
    }
}
