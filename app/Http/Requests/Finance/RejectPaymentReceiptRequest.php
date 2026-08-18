<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class RejectPaymentReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('finance.review_payments') === true;
    }

    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }
}
