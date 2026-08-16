<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function view(User $user, Task $task): bool
    {
        return $user->can('tasks.view') && ($user->can('tasks.view_all') || $task->assignee_id === $user->id);
    }

    public function update(User $user, Task $task): bool
    {
        return $user->can('tasks.update') && $this->view($user, $task);
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->can('tasks.delete') && $this->view($user, $task);
    }

    public function updateStatus(User $user, Task $task): bool
    {
        return $user->can('tasks.update_status') && $this->view($user, $task);
    }
}
