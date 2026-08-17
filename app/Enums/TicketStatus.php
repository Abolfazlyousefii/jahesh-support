<?php

namespace App\Enums;

enum TicketStatus: string
{
    case New = 'new';
    case InReview = 'in_review';
    case InProgress = 'in_progress';
    case WaitingCustomer = 'waiting_customer';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::New => 'جدید',
            self::InReview => 'در حال بررسی',
            self::InProgress => 'در حال انجام',
            self::WaitingCustomer => 'منتظر پاسخ مشتری',
            self::Resolved => 'حل شده',
            self::Closed => 'بسته شده',
        };
    }

    public function intent(): string
    {
        return match ($this) {
            self::Resolved => 'success',
            self::InProgress => 'info',
            self::WaitingCustomer => 'warning',
            self::Closed => 'danger',
            default => 'neutral',
        };
    }

    public function isClosed(): bool
    {
        return $this === self::Closed;
    }
}
