<?php

namespace App\Support;

final class NumberNormalizer
{
    public static function latinDigits(?string $value): string
    {
        return strtr(trim((string) $value), [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
    }

    public static function money(mixed $value): string
    {
        $value = self::latinDigits((string) $value);

        return preg_replace('/[^0-9]/', '', $value) ?? '';
    }

    public static function cardNumber(?string $value): string
    {
        return preg_replace('/\D+/', '', self::latinDigits($value)) ?? '';
    }

    public static function iban(?string $value): string
    {
        $value = strtoupper(self::latinDigits($value));

        return preg_replace('/[^A-Z0-9]/', '', $value) ?? '';
    }
}
