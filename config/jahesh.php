<?php

return [
    'admin' => [
        'name' => env('JAHESH_ADMIN_NAME'),
        'phone' => env('JAHESH_ADMIN_PHONE'),
        'password' => env('JAHESH_ADMIN_PASSWORD'),
    ],
    'password_reset' => [
        'expire_minutes' => (int) env('CUSTOMER_PASSWORD_RESET_OTP_EXPIRE_MINUTES', 5),
        'verified_minutes' => (int) env('CUSTOMER_PASSWORD_RESET_VERIFIED_MINUTES', 10),
        'max_attempts' => (int) env('CUSTOMER_PASSWORD_RESET_MAX_ATTEMPTS', 5),
        'cooldown_seconds' => (int) env('CUSTOMER_PASSWORD_RESET_COOLDOWN_SECONDS', 60),
    ],
    'otp' => [
        'driver' => env('OTP_DRIVER', 'auto'),
        'expire_minutes' => (int) env('OTP_EXPIRE_MINUTES', 5),
        'max_attempts' => (int) env('OTP_MAX_ATTEMPTS', 5),
        'cooldown_seconds' => (int) env('OTP_COOLDOWN_SECONDS', 60),
    ],
];
