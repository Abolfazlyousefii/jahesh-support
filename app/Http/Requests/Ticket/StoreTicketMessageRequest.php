<?php

namespace App\Http\Requests\Ticket;

use App\Enums\TicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:10000'],
            'after_reply_status' => [
                'nullable',
                Rule::in([
                    TicketStatus::WaitingCustomer->value,
                    TicketStatus::InProgress->value,
                    TicketStatus::Resolved->value,
                ]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => 'متن پیام را وارد کنید.',
            'after_reply_status.in' => 'وضعیت بعد از پاسخ معتبر نیست.',
        ];
    }
}
