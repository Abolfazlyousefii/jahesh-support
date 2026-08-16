<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Task> */
class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'customer_id' => null,
            'assignee_id' => User::factory(),
            'created_by' => User::factory(),
            'priority' => TaskPriority::Normal,
            'status' => TaskStatus::New,
            'start_date' => today(),
            'due_date' => today()->addDays(3),
            'completed_at' => null,
        ];
    }
}
