<?php

namespace App\Http\Requests\Task;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'assignee_id' => $this->user()->can('tasks.assign') ? $this->input('assignee_id') : $this->user()->id,
            'customer_id' => $this->filled('customer_id') ? $this->input('customer_id') : null,
            'start_date' => $this->filled('start_date') ? $this->input('start_date') : null,
            'due_date' => $this->filled('due_date') ? $this->input('due_date') : null,
            'priority' => $this->input('priority', TaskPriority::Normal->value),
            'status' => $this->input('status', TaskStatus::New->value),
        ]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')->whereNull('deleted_at')],
            'assignee_id' => ['required', 'integer', Rule::exists('users', 'id')->where(fn ($query) => $query->where('is_active', true)->whereNull('deleted_at'))],
            'priority' => ['required', new Enum(TaskPriority::class)],
            'status' => ['required', new Enum(TaskStatus::class)],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'عنوان تسک را وارد کنید.',
            'customer_id.exists' => 'مشتری انتخاب‌شده معتبر نیست.',
            'assignee_id.required' => 'مسئول تسک را انتخاب کنید.',
            'assignee_id.exists' => 'مسئول باید یک عضو فعال تیم باشد.',
            'priority.enum' => 'اولویت انتخاب‌شده معتبر نیست.',
            'status.enum' => 'وضعیت انتخاب‌شده معتبر نیست.',
            'start_date.date' => 'تاریخ شروع معتبر نیست.',
            'due_date.date' => 'ددلاین معتبر نیست.',
            'due_date.after_or_equal' => 'ددلاین نباید قبل از تاریخ شروع باشد.',
        ];
    }
}
