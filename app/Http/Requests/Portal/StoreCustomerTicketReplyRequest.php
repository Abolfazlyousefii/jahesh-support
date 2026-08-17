<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerTicketReplyRequest extends FormRequest
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
        return ['body.required' => 'پیام خود را بنویسید.'];
    }
}
