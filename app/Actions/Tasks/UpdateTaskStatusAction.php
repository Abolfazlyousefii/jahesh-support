<?php

namespace App\Actions\Tasks;

use App\Enums\TaskStatus;
use App\Enums\TicketStatus;
use App\Models\Task;
use App\Models\User;
use App\Services\Activity\ActivityLogger;
use App\Services\Notifications\InAppNotifier;
use App\Services\Sms\SmsNotifier;
use App\Support\TaskStatusManager;
use App\Support\TicketWorkflow;

class UpdateTaskStatusAction
{
    public function __construct(
        private readonly TaskStatusManager $statuses,
        private readonly TicketWorkflow $ticketWorkflow,
        private readonly SmsNotifier $sms,
        private readonly InAppNotifier $notifications,
        private readonly ActivityLogger $activity,
    ) {}

    public function execute(Task $task, TaskStatus $status, ?User $actor = null): Task
    {
        $task->loadMissing('sourceTicket');
        $previousStatus = $task->status;
        $ticketPreviousStatus = $task->sourceTicket?->status;
        $ticketWasResolved = $ticketPreviousStatus === TicketStatus::Resolved;

        $task->update($this->statuses->attributes($status, $task));
        $this->ticketWorkflow->syncFromTaskStatus($task, $status);
        $task->refresh();

        if ($previousStatus !== $task->status) {
            $this->activity->record(
                'task.status_changed',
                $task,
                $actor,
                'وضعیت تسک تغییر کرد.',
                ['status' => $previousStatus],
                ['status' => $task->status],
            );
        }

        if ($task->sourceTicket !== null) {
            $task->sourceTicket->refresh();

            if ($ticketPreviousStatus !== null && $ticketPreviousStatus !== $task->sourceTicket->status) {
                $this->activity->record(
                    'ticket.status_changed',
                    $task->sourceTicket,
                    $actor,
                    'وضعیت تیکت به‌صورت خودکار بر اساس وضعیت تسک تغییر کرد.',
                    ['status' => $ticketPreviousStatus],
                    ['status' => $task->sourceTicket->status],
                    ['task_id' => $task->id, 'source' => 'task_sync'],
                );
            }

            if (! $ticketWasResolved && $task->sourceTicket->status === TicketStatus::Resolved) {
                $this->sms->ticketResolved($task->sourceTicket);
                $this->notifications->ticketResolved($task->sourceTicket);
            }
        }

        return $task;
    }
}
