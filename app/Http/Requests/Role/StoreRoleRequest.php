<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('roles', 'name')],
            'title' => ['required', 'string', 'max:255'],
            'permission_ids' => ['array'],
            'permission_ids.*' => ['integer', Rule::exists('permissions', 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'نام فنی نقش را وارد کنید.',
            'name.regex' => 'نام فنی فقط می‌تواند شامل حروف انگلیسی کوچک، عدد و خط تیره باشد.',
            'name.unique' => 'این نام فنی قبلاً ثبت شده است.',
            'title.required' => 'عنوان فارسی نقش را وارد کنید.',
            'permission_ids.*.exists' => 'یکی از دسترسی‌های انتخاب‌شده معتبر نیست.',
        ];
    }
}
