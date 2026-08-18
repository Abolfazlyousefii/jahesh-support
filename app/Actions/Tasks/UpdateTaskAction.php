<?php

namespace App\Actions\Tasks;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use App\Support\TaskStatusManager;
use App\Support\TicketWorkflow;

class UpdateTaskAction
{
    public function __construct(
        private readonly TaskStatusManager $statuses,
        private readonly TicketWorkflow $ticketWorkflow,
    ) {}

    public function execute(User $actor, Task $task, array $data): Task
    {
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

        $task->loadMissing('sourceTicket');
        $this->ticketWorkflow->syncFromTaskStatus($task, $status);

        return $task;
    }
}
