<?php

namespace App\Http\Requests\Portal;

use App\Support\CustomerPasswordRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('customer') !== null;
    }

    public function rules(): array
    {
        $customer = $this->user('customer');

        return [
            'current_password' => [
                Rule::requiredIf($customer !== null && filled($customer->password)),
                'nullable',
                'string',
                'max:255',
            ],
            'password' => CustomerPasswordRules::rules(),
        ];
    }

    public function messages(): array
    {
        return array_merge(CustomerPasswordRules::messages(), [
            'current_password.required' => 'رمز عبور فعلی را وارد کنید.',
        ]);
    }
}
