<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

final class CustomerPasswordRules
{
    public static function rules(): array
    {
        return [
            'required',
            'string',
            Password::min(8),
            'regex:/[A-Z]/',
            'regex:/[0-9]/',
            'confirmed',
            'max:255',
        ];
    }

    public static function messages(string $field = 'password'): array
    {
        return [
            $field.'.required' => 'رمز عبور جدید را وارد کنید.',
            $field.'.min' => 'رمز عبور باید حداقل ۸ کاراکتر باشد.',
            $field.'.regex' => 'رمز عبور باید حداقل یک حرف بزرگ انگلیسی و یک عدد داشته باشد.',
            $field.'.confirmed' => 'تکرار رمز عبور با رمز جدید یکسان نیست.',
            $field.'.max' => 'رمز عبور بیش از حد طولانی است.',
        ];
    }
}
