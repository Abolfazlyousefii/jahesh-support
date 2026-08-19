<?php

namespace App\Actions\Tickets;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\TicketMessageType;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Activity\ActivityLogger;
use App\Services\Notifications\InAppNotifier;
use App\Services\Sms\SmsNotifier;
use App\Support\TaskStatusManager;
use App\Support\TicketWorkflow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ConvertTicketToTaskAction
{
    public function __construct(
        private readonly TaskStatusManager $taskStatuses,
        private readonly TicketWorkflow $ticketWorkflow,
        private readonly SmsNotifier $sms,
        private readonly InAppNotifier $notifications,
        private readonly ActivityLogger $activity,
    ) {}

    public function execute(User $actor, Ticket $ticket, array $data): Task
    {
        $beforeTicketStatus = $ticket->status;

        $task = DB::transaction(function () use ($actor, $ticket, $data) {
            $lockedTicket = Ticket::query()->lockForUpdate()->findOrFail($ticket->id);
            if ($lockedTicket->task()->withTrashed()->exists()) {
                throw ValidationException::withMessages(['ticket' => 'این تیکت قبلاً به تسک تبدیل شده است.']);
            }

            $firstMessage = $lockedTicket->messages()
                ->where('message_type', TicketMessageType::Public)
                ->oldest('id')
                ->value('body');

            $task = Task::query()->create([
                'title' => $data['title'],
                'description' => "برگرفته از تیکت #{$lockedTicket->id}\n\n".Str::limit((string) $firstMessage, 1500),
                'customer_id' => $lockedTicket->customer_id,
                'source_ticket_id' => $lockedTicket->id,
                'assignee_id' => $data['assignee_id'],
                'created_by' => $actor->id,
                'priority' => TaskPriority::from($lockedTicket->priority->value)->value,
                'start_date' => $data['start_date'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                ...$this->taskStatuses->attributes(TaskStatus::New),
            ]);

            $this->ticketWorkflow->afterTaskCreated($lockedTicket);

            return $task;
        });

        $ticket->refresh();

        $this->activity->record(
            'ticket.converted_to_task',
            $ticket,
            $actor,
            'تیکت به تسک اجرایی تبدیل شد.',
            new: ['task_id' => $task->id],
            metadata: ['task_id' => $task->id],
        );

        $this->activity->record(
            'task.created',
            $task,
            $actor,
            'تسک از روی تیکت ایجاد شد.',
            new: $this->activity->snapshot($task, [
                'title', 'customer_id', 'assignee_id', 'priority', 'status', 'start_date', 'due_date',
            ]),
            metadata: ['source_ticket_id' => $ticket->id],
        );

        if ($beforeTicketStatus !== $ticket->status) {
            $this->activity->record(
                'ticket.status_changed',
                $ticket,
                $actor,
                'وضعیت تیکت پس از تبدیل به تسک تغییر کرد.',
                ['status' => $beforeTicketStatus],
                ['status' => $ticket->status],
                ['task_id' => $task->id, 'source' => 'task_conversion'],
            );
        }

        $this->sms->taskAssigned($task, $actor);
        $this->notifications->taskAssigned($task, $actor);

        return $task;
    }
}
