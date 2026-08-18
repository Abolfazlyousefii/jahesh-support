<?php

namespace App\Http\Requests\Portal;

use App\Support\NumberNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('customer') !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'amount' => NumberNormalizer::money($this->input('amount')),
            'tracking_code' => NumberNormalizer::latinDigits($this->input('tracking_code')) ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'bank_account_id' => [
                'required',
                Rule::exists('financial_bank_accounts', 'id')->where(fn ($query) => $query
                    ->where('is_active', true)
                    ->whereNull('deleted_at')),
            ],
            'amount' => ['required', 'integer', 'min:1', 'max:999999999999'],
            'paid_at' => ['required', 'date', 'before_or_equal:today'],
            'tracking_code' => ['nullable', 'string', 'max:100'],
            'customer_note' => ['nullable', 'string', 'max:1000'],
            'receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'receipt.mimes' => 'فیش باید با فرمت JPG، PNG یا PDF باشد.',
            'receipt.max' => 'حداکثر حجم فایل فیش ۵ مگابایت است.',
            'paid_at.before_or_equal' => 'تاریخ پرداخت نمی‌تواند مربوط به آینده باشد.',
        ];
    }
}
