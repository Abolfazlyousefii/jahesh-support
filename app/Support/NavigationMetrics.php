<?php

namespace App\Support;

use App\Models\CustomerPaymentReceipt;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class NavigationMetrics
{
    /**
     * These counters intentionally represent actionable work, not total records.
     *
     * @return array{overdue_tasks:int,attention_tickets:int,pending_receipts:int}
     */
    public function for(User $user): array
    {
        $overdueTasks = $user->can('tasks.view')
            ? Task::query()->visibleTo($user)->overdue()->count()
            : 0;

        $attentionTickets = $user->can('tickets.view')
            ? Ticket::query()
                ->visibleTo($user)
                ->open()
                ->whereNotNull('last_customer_message_at')
                ->where(function (Builder $query): void {
                    $query->whereNull('last_staff_message_at')
                        ->orWhereColumn('last_customer_message_at', '>', 'last_staff_message_at');
                })
                ->count()
            : 0;

        $pendingReceipts = $user->can('finance.review_payments')
            ? CustomerPaymentReceipt::query()->pending()->count()
            : 0;

        return [
            'overdue_tasks' => $overdueTasks,
            'attention_tickets' => $attentionTickets,
            'pending_receipts' => $pendingReceipts,
        ];
    }
}
