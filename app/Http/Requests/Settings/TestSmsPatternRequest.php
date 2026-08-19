<?php

namespace App\Http\Requests\Settings;

use App\Rules\IranianMobile;
use App\Services\Sms\SmsPatternCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TestSmsPatternRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.sms.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', new IranianMobile],
            'pattern_key' => ['required', Rule::in(array_keys(SmsPatternCatalog::definitions()))],
        ];
    }
}
