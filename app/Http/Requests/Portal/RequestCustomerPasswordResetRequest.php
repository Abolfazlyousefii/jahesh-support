<?php

namespace App\Http\Requests\Portal;

use App\Rules\IranianMobile;
use App\Support\PhoneNormalizer;
use Illuminate\Foundation\Http\FormRequest;

class RequestCustomerPasswordResetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => PhoneNormalizer::normalize($this->string('phone')->toString()),
        ]);
    }

    public function rules(): array
    {
        return ['phone' => ['required', new IranianMobile]];
    }

    public function messages(): array
    {
        return ['phone.required' => 'شماره موبایل را وارد کنید.'];
    }
}
