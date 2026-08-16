<?php

namespace App\Http\Requests\Team;

use App\Rules\IranianMobile;
use App\Support\PhoneNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreTeamMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => PhoneNormalizer::normalize($this->string('phone')->toString()),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', new IranianMobile, Rule::unique('users', 'phone')],
            'password' => ['required', 'confirmed', Password::min(8)],
            'role_ids' => ['array'],
            'role_ids.*' => ['integer', Rule::exists('roles', 'id')],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'نام و نام خانوادگی را وارد کنید.',
            'phone.required' => 'شماره موبایل را وارد کنید.',
            'phone.unique' => 'این شماره موبایل قبلاً ثبت شده است.',
            'password.required' => 'رمز عبور را وارد کنید.',
            'password.confirmed' => 'تکرار رمز عبور یکسان نیست.',
            'password.min' => 'رمز عبور باید حداقل ۸ نویسه باشد.',
            'role_ids.*.exists' => 'یکی از نقش‌های انتخاب‌شده معتبر نیست.',
        ];
    }
}
