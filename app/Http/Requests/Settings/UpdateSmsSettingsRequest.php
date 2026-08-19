<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\SmsSetting;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateSmsSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.sms.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'enabled' => $this->boolean('enabled'),
            'internal_recipient_user_ids' => array_values(array_filter(
                (array) $this->input('internal_recipient_user_ids', []),
                fn ($id) => filled($id),
            )),
        ]);
    }


    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->boolean('enabled')) {
                return;
            }

            if (blank($this->input('webservice_username'))) {
                $validator->errors()->add('webservice_username', 'برای فعال‌سازی پیامک، نام کاربری وب‌سرویس الزامی است.');
            }

            $hasStoredPassword = filled(SmsSetting::current()->webservice_password);
            if (blank($this->input('webservice_password')) && ! $hasStoredPassword) {
                $validator->errors()->add('webservice_password', 'برای فعال‌سازی پیامک، رمز وب‌سرویس الزامی است.');
            }
        });
    }

    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
            'webservice_username' => ['nullable', 'string', 'max:150'],
            'webservice_password' => ['nullable', 'string', 'max:500'],
            'internal_recipient_user_ids' => ['array'],
            'internal_recipient_user_ids.*' => [
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('is_active', true)->whereNull('deleted_at')),
            ],
            'patterns' => ['nullable', 'array'],
            'patterns.*.enabled' => ['nullable', 'boolean'],
            'patterns.*.body_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
