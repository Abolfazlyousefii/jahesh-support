<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class VoidLedgerEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('finance.void_entry') === true;
    }

    public function rules(): array
    {
        return [
            'void_reason' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }
}
