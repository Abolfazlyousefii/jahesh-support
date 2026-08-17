<?php

return [
    'admin' => [
        'name' => env('JAHESH_ADMIN_NAME'),
        'phone' => env('JAHESH_ADMIN_PHONE'),
        'password' => env('JAHESH_ADMIN_PASSWORD'),
    ],
    'otp' => [
        'driver' => env('OTP_DRIVER', env('APP_ENV', 'production') === 'local' ? 'log' : 'disabled'),
        'expire_minutes' => (int) env('OTP_EXPIRE_MINUTES', 5),
        'max_attempts' => (int) env('OTP_MAX_ATTEMPTS', 5),
        'cooldown_seconds' => (int) env('OTP_COOLDOWN_SECONDS', 60),
    ],
];
