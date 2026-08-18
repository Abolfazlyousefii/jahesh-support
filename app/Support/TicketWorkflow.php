<?php

namespace App\Support;

use App\Enums\TaskStatus;
use App\Enums\TicketStatus;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

final class TicketWorkflow
{
    public function ensureWritable(Ticket $ticket): void
    {
        if ($ticket->status->isClosed()) {
            throw ValidationException::withMessages(['ticket' => 'این درخواست بسته شده و قابل تغییر نیست.']);
        }
    }

    public function assign(Ticket $ticket, int $assigneeId): void
    {
        $this->ensureWritable($ticket);

        $attributes = [
            'assigned_to' => $assigneeId,
            // مسئول جدید باید پیام‌های قبلی مشتری را به‌عنوان خوانده‌نشده ببیند.
            'assignee_last_read_at' => null,
        ];

        if ($ticket->status === TicketStatus::New) {
            $attributes['status'] = TicketStatus::InReview;
        }

        $ticket->update($attributes);
    }

    public function afterCustomerReply(Ticket $ticket, Carbon $messageAt): void
    {
        $attributes = ['last_customer_message_at' => $messageAt];

        if (in_array($ticket->status, [TicketStatus::WaitingCustomer, TicketStatus::Resolved], true)) {
            $attributes['status'] = TicketStatus::InReview;
            $attributes['closed_at'] = null;
        }

        $ticket->update($attributes);
    }

    public function afterStaffPublicReply(Ticket $ticket, Carbon $messageAt, ?TicketStatus $statusAfterReply = null): void
    {
        $attributes = ['last_staff_message_at' => $messageAt];

        if ($statusAfterReply !== null) {
            $attributes = [
                ...$attributes,
                ...$this->statusAttributes($ticket, $statusAfterReply),
            ];
        }

        $ticket->update($attributes);
    }

    public function afterInternalNote(Ticket $ticket): void
    {
        $this->ensureWritable($ticket);
        $ticket->touch();
    }

    public function afterTaskCreated(Ticket $ticket): void
    {
        $this->ensureWritable($ticket);
        $ticket->update(['status' => TicketStatus::InProgress, 'closed_at' => null]);
    }

    public function syncFromTaskStatus(Task $task, TaskStatus $status): void
    {
        if ($task->source_ticket_id === null) {
            return;
        }

        $ticket = $task->sourceTicket;
        if ($ticket === null || $ticket->trashed() || $ticket->status->isClosed()) {
            return;
        }

        if ($status === TaskStatus::Completed) {
            $ticket->update(['status' => TicketStatus::Resolved, 'closed_at' => null]);
            return;
        }

        if ($ticket->status === TicketStatus::Resolved && in_array($status, [
            TaskStatus::New,
            TaskStatus::Pending,
            TaskStatus::InProgress,
            TaskStatus::Review,
        ], true)) {
            $ticket->update(['status' => TicketStatus::InProgress, 'closed_at' => null]);
        }
    }

    public function markStaffRead(Ticket $ticket, User $user): void
    {
        if ($ticket->assigned_to !== $user->id) {
            return;
        }

        Ticket::withoutTimestamps(function () use ($ticket) {
            $ticket->forceFill(['assignee_last_read_at' => now()])->saveQuietly();
        });
    }

    public function markCustomerRead(Ticket $ticket): void
    {
        Ticket::withoutTimestamps(function () use ($ticket) {
            $ticket->forceFill(['customer_last_read_at' => now()])->saveQuietly();
        });
    }

    public function statusAttributes(Ticket $ticket, TicketStatus $status): array
    {
        $this->ensureWritable($ticket);

        return [
            'status' => $status,
            'closed_at' => $status === TicketStatus::Closed ? now() : null,
        ];
    }
}
