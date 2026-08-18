<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Legacy Jahesh database
    |--------------------------------------------------------------------------
    |
    | backup.sql را در یک دیتابیس موقت جدا (به‌صورت پیش‌فرض jahesh_legacy)
    | Restore کنید. Importer فقط از این Connection می‌خواند و هیچ تغییری
    | روی دیتابیس قدیمی اعمال نمی‌کند.
    |
    */
    'source' => env('LEGACY_IMPORT_SOURCE', 'jahesh-v1'),

    'connection' => [
        'driver' => env('LEGACY_DB_CONNECTION', 'mysql'),
        'host' => env('LEGACY_DB_HOST', '127.0.0.1'),
        'port' => env('LEGACY_DB_PORT', '3306'),
        'database' => env('LEGACY_DB_DATABASE', 'jahesh_legacy'),
        'username' => env('LEGACY_DB_USERNAME', 'root'),
        'password' => env('LEGACY_DB_PASSWORD', ''),
        'unix_socket' => env('LEGACY_DB_SOCKET', ''),
        'charset' => env('LEGACY_DB_CHARSET', 'utf8mb4'),
        'collation' => env('LEGACY_DB_COLLATION', 'utf8mb4_unicode_ci'),
        'prefix' => '',
        'prefix_indexes' => true,
        'strict' => true,
        'engine' => null,
    ],
];
