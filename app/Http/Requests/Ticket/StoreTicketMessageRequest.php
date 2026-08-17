<?php

namespace App\Http\Requests\Ticket;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['body' => ['required', 'string', 'max:10000']];
    }

    public function messages(): array
    {
        return ['body.required' => 'متن پیام را وارد کنید.'];
    }
}
