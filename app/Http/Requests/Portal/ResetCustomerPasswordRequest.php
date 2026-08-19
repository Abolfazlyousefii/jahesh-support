<?php

namespace App\Http\Requests\Portal;

use App\Support\CustomerPasswordRules;
use Illuminate\Foundation\Http\FormRequest;

class ResetCustomerPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['password' => CustomerPasswordRules::rules()];
    }

    public function messages(): array
    {
        return CustomerPasswordRules::messages();
    }
}
