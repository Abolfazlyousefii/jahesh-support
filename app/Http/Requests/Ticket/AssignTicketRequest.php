<?php

namespace App\Http\Requests\Ticket;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['assignee_id' => ['required', 'integer', Rule::exists('users', 'id')->where(
            fn ($query) => $query->where('is_active', true)->whereNull('deleted_at'),
        )]];
    }

    public function messages(): array
    {
        return ['assignee_id.exists' => 'مسئول باید یک عضو فعال تیم باشد.'];
    }
}
