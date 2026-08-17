<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;

class VerifyCustomerOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['code' => ['required', 'digits:6']];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'کد ورود را وارد کنید.',
            'code.digits' => 'کد ورود باید ۶ رقم باشد.',
        ];
    }
}
