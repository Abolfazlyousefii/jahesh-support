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

class UpdateTaskAction
{
    private const GENERAL_FIELDS = ['title', 'description', 'customer_id', 'priority', 'start_date', 'due_date'];

    public function __construct(
        private readonly TaskStatusManager $statuses,
        private readonly TicketWorkflow $ticketWorkflow,
        private readonly SmsNotifier $sms,
        private readonly InAppNotifier $notifications,
        private readonly ActivityLogger $activity,
    ) {}

    public function execute(User $actor, Task $task, array $data): Task
    {
        $task->loadMissing('sourceTicket');
        $beforeGeneral = $this->activity->snapshot($task, self::GENERAL_FIELDS);
        $previousAssignee = $task->assignee_id;
        $previousStatus = $task->status;
        $ticketPreviousStatus = $task->sourceTicket?->status;
        $ticketWasResolved = $ticketPreviousStatus === TicketStatus::Resolved;

        $assigneeId = $actor->can('tasks.assign') ? $data['assignee_id'] : $task->assignee_id;
        $status = TaskStatus::from($data['status']);

        $task->update([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'customer_id' => $data['customer_id'] ?? null,
            'assignee_id' => $assigneeId,
            'priority' => $data['priority'],
            'start_date' => $data['start_date'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            ...$this->statuses->attributes($status, $task),
        ]);

        $this->ticketWorkflow->syncFromTaskStatus($task, $status);
        $task->refresh();

        $generalChanges = $this->activity->changed(
            $beforeGeneral,
            $this->activity->snapshot($task, self::GENERAL_FIELDS),
        );

        if ($generalChanges['old'] !== [] || $generalChanges['new'] !== []) {
            $this->activity->record(
                'task.updated',
                $task,
                $actor,
                'اطلاعات تسک ویرایش شد.',
                $generalChanges['old'],
                $generalChanges['new'],
            );
        }

        if ($previousAssignee !== $task->assignee_id) {
            $this->activity->record(
                'task.assigned',
                $task,
                $actor,
                'مسئول تسک تغییر کرد.',
                ['assignee_id' => $previousAssignee],
                ['assignee_id' => $task->assignee_id],
            );

            $this->sms->taskAssigned($task, $actor);
            $this->notifications->taskAssigned($task, $actor);
        }

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
