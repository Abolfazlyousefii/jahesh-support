<?php

namespace App\Actions\Tasks;

use App\Enums\TaskStatus;
use App\Enums\TicketStatus;
use App\Models\Task;
use App\Services\Sms\SmsNotifier;
use App\Support\TaskStatusManager;
use App\Support\TicketWorkflow;

class UpdateTaskStatusAction
{
    public function __construct(
        private readonly TaskStatusManager $statuses,
        private readonly TicketWorkflow $ticketWorkflow,
        private readonly SmsNotifier $sms,
    ) {}

    public function execute(Task $task, TaskStatus $status): Task
    {
        $task->loadMissing('sourceTicket');
        $ticketWasResolved = $task->sourceTicket?->status === TicketStatus::Resolved;

        $task->update($this->statuses->attributes($status, $task));
        $this->ticketWorkflow->syncFromTaskStatus($task, $status);

        if ($task->sourceTicket !== null) {
            $task->sourceTicket->refresh();
            if (! $ticketWasResolved && $task->sourceTicket->status === TicketStatus::Resolved) {
                $this->sms->ticketResolved($task->sourceTicket);
            }
        }

        return $task;
    }
}
