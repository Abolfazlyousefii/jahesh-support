<?php

namespace App\Rules;

use App\Support\PhoneNormalizer;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class IranianMobile implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! PhoneNormalizer::isValid($value)) {
            $fail('شماره موبایل باید یک شماره معتبر ایرانی باشد.');
        }
    }
}
