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

    public function isWorkflow(): bool
    {
        return in_array($this, self::workflow(), true);
    }

    /**
     * @return array<int, self>
     */
    public static function workflow(): array
    {
        return [
            self::New,
            self::Pending,
            self::InProgress,
            self::Review,
        ];
    }

    /**
     * @return array<int, self>
     */
    public static function secondary(): array
    {
        return [
            self::Completed,
            self::Paused,
            self::Cancelled,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function workflowValues(): array
    {
        return array_map(
            static fn (self $status): string => $status->value,
            self::workflow(),
        );
    }
}
