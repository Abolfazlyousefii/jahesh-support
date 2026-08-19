<?php

namespace App\Actions\Tickets;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\TicketMessageType;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
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
    ) {}

    public function execute(User $actor, Ticket $ticket, array $data): Task
    {
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

        $this->sms->taskAssigned($task, $actor);

        return $task;
    }
}
