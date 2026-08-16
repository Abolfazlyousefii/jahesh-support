<?php

namespace App\Actions\Tasks;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Support\TaskStatusManager;

class UpdateTaskStatusAction
{
    public function __construct(private readonly TaskStatusManager $statuses) {}

    public function execute(Task $task, TaskStatus $status): Task
    {
        $task->update($this->statuses->attributes($status, $task));

        return $task;
    }
}
