<?php

namespace App\Http\Requests\Task;

use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateTaskStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['status' => ['required', new Enum(TaskStatus::class)]];
    }

    public function messages(): array
    {
        return ['status.enum' => 'وضعیت انتخاب‌شده معتبر نیست.'];
    }
}
