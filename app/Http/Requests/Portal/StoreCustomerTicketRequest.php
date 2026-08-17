<?php

namespace App\Http\Requests\Portal;

use App\Enums\TicketPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreCustomerTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'priority' => ['required', new Enum(TicketPriority::class)],
            'description' => ['required', 'string', 'max:10000'],
        ];
    }

    public function messages(): array
    {
        return [
            'subject.required' => 'موضوع درخواست را وارد کنید.',
            'priority.enum' => 'اولویت انتخاب‌شده معتبر نیست.',
            'description.required' => 'توضیحات درخواست را وارد کنید.',
        ];
    }
}
