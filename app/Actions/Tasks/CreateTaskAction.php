<?php

namespace App\Actions\Tasks;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use App\Services\Activity\ActivityLogger;
use App\Services\Notifications\InAppNotifier;
use App\Services\Sms\SmsNotifier;
use App\Support\TaskStatusManager;

class CreateTaskAction
{
    public function __construct(
        private readonly TaskStatusManager $statuses,
        private readonly SmsNotifier $sms,
        private readonly InAppNotifier $notifications,
        private readonly ActivityLogger $activity,
    ) {}

    public function execute(User $actor, array $data): Task
    {
        $assigneeId = $actor->can('tasks.assign') ? $data['assignee_id'] : $actor->id;
        $status = TaskStatus::from($data['status']);

        $task = Task::query()->create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'customer_id' => $data['customer_id'] ?? null,
            'assignee_id' => $assigneeId,
            'created_by' => $actor->id,
            'priority' => $data['priority'],
            'start_date' => $data['start_date'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            ...$this->statuses->attributes($status),
        ]);

        $this->activity->record(
            'task.created',
            $task,
            $actor,
            'تسک جدید ایجاد شد.',
            new: $this->activity->snapshot($task, [
                'title', 'description', 'customer_id', 'assignee_id', 'priority', 'status', 'start_date', 'due_date',
            ]),
        );

        $this->sms->taskAssigned($task, $actor);
        $this->notifications->taskAssigned($task, $actor);

        return $task;
    }
}
