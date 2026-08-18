<?php

namespace App\Http\Requests\Finance;

use App\Enums\LedgerEntryType;
use App\Support\NumberNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLedgerEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('finance.create_entry') === true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['amount' => NumberNormalizer::money($this->input('amount'))]);
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(LedgerEntryType::class)],
            'amount' => ['required', 'integer', 'min:1', 'max:999999999999'],
            'description' => ['required', 'string', 'max:500'],
            'reference' => ['nullable', 'string', 'max:150'],
            'entry_date' => ['required', 'date'],
        ];
    }
}
