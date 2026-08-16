<?php

namespace App\Support;

use App\Enums\TaskStatus;
use App\Models\Task;

final class TaskStatusManager
{
    public function attributes(TaskStatus $status, ?Task $task = null): array
    {
        return [
            'status' => $status,
            'completed_at' => $status === TaskStatus::Completed
                ? ($task?->completed_at ?? now())
                : null,
        ];
    }
}
