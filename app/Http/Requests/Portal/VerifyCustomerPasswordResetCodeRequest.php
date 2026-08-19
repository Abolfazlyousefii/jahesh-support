<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;

class VerifyCustomerPasswordResetCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $code = strtr($this->string('code')->toString(), [
            '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4',
            '۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
            '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4',
            '٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9',
        ]);

        $this->merge(['code' => $code]);
    }

    public function rules(): array
    {
        return ['code' => ['required', 'digits:6']];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'کد بازیابی را وارد کنید.',
            'code.digits' => 'کد بازیابی باید ۶ رقم باشد.',
        ];
    }
}
