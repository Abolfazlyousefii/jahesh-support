<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\PhoneNormalizer;
use Illuminate\Database\Seeder;

class AdministratorSeeder extends Seeder
{
    public function run(): void
    {
        $name = config('jahesh.admin.name');
        $phone = PhoneNormalizer::normalize(config('jahesh.admin.phone'));
        $password = config('jahesh.admin.password');

        if (! $name || ! $password || ! PhoneNormalizer::isValid($phone)) {
            $this->command?->warn('Administrator ساخته نشد؛ JAHESH_ADMIN_NAME، JAHESH_ADMIN_PHONE و JAHESH_ADMIN_PASSWORD را در ENV تنظیم کنید.');

            return;
        }

        $user = User::query()->updateOrCreate(
            ['phone' => $phone],
            ['name' => $name, 'password' => $password, 'is_active' => true],
        );
        $user->assignRole('super-admin');
        $this->command?->info('Administrator اولیه ایجاد یا به‌روزرسانی شد.');
    }
}
