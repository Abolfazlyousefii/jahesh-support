<?php

namespace App\Actions\Tasks;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Support\TaskStatusManager;
use App\Support\TicketWorkflow;

class UpdateTaskStatusAction
{
    public function __construct(
        private readonly TaskStatusManager $statuses,
        private readonly TicketWorkflow $ticketWorkflow,
    ) {}

    public function execute(Task $task, TaskStatus $status): Task
    {
        $task->update($this->statuses->attributes($status, $task));
        $task->loadMissing('sourceTicket');
        $this->ticketWorkflow->syncFromTaskStatus($task, $status);

        return $task;
    }
}
