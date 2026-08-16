<?php

namespace App\Http\Requests\Task;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        /** @var Task $task */
        $task = $this->route('task');

        $this->merge([
            'assignee_id' => $this->user()->can('tasks.assign') ? $this->input('assignee_id') : $task->assignee_id,
            'customer_id' => $this->filled('customer_id') ? $this->input('customer_id') : null,
            'start_date' => $this->filled('start_date') ? $this->input('start_date') : null,
            'due_date' => $this->filled('due_date') ? $this->input('due_date') : null,
        ]);
    }

    public function rules(): array
    {
        /** @var Task $task */
        $task = $this->route('task');

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')->where(
                fn ($query) => $query->whereNull('deleted_at')->orWhere('id', $task->customer_id),
            )],
            'assignee_id' => ['required', 'integer', Rule::exists('users', 'id')->where(fn ($query) => $query->where('is_active', true)->whereNull('deleted_at'))],
            'priority' => ['required', new Enum(TaskPriority::class)],
            'status' => ['required', new Enum(TaskStatus::class)],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    public function messages(): array
    {
        return (new StoreTaskRequest)->messages();
    }
}
