<?php

namespace App\Http\Requests\Finance;

use App\Support\NumberNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('finance.manage_bank_accounts') === true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'card_number' => NumberNormalizer::cardNumber($this->input('card_number')) ?: null,
            'iban' => NumberNormalizer::iban($this->input('iban')) ?: null,
            'account_number' => NumberNormalizer::latinDigits($this->input('account_number')) ?: null,
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        return [
            'bank_name' => ['required', 'string', 'max:100'],
            'account_holder' => ['required', 'string', 'max:150'],
            'card_number' => ['nullable', 'regex:/^\d{16}$/'],
            'iban' => ['nullable', 'regex:/^IR\d{24}$/'],
            'account_number' => ['nullable', 'string', 'max:64'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if (! $this->input('card_number') && ! $this->input('iban') && ! $this->input('account_number')) {
                $validator->errors()->add('card_number', 'حداقل یکی از شماره کارت، شبا یا شماره حساب را وارد کنید.');
            }
        }];
    }
}
