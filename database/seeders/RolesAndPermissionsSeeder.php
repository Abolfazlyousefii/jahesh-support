<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            ['name' => 'dashboard.view', 'title' => 'مشاهده داشبورد', 'group' => 'داشبورد'],
            ['name' => 'team.view', 'title' => 'مشاهده اعضای تیم', 'group' => 'اعضای تیم'],
            ['name' => 'team.create', 'title' => 'ایجاد عضو تیم', 'group' => 'اعضای تیم'],
            ['name' => 'team.update', 'title' => 'ویرایش عضو تیم', 'group' => 'اعضای تیم'],
            ['name' => 'team.delete', 'title' => 'حذف عضو تیم', 'group' => 'اعضای تیم'],
            ['name' => 'team.assign_roles', 'title' => 'تخصیص نقش به اعضا', 'group' => 'اعضای تیم'],
            ['name' => 'roles.view', 'title' => 'مشاهده نقش‌ها', 'group' => 'نقش‌ها و دسترسی‌ها'],
            ['name' => 'roles.create', 'title' => 'ایجاد نقش', 'group' => 'نقش‌ها و دسترسی‌ها'],
            ['name' => 'roles.update', 'title' => 'ویرایش نقش و دسترسی‌ها', 'group' => 'نقش‌ها و دسترسی‌ها'],
            ['name' => 'roles.delete', 'title' => 'حذف نقش', 'group' => 'نقش‌ها و دسترسی‌ها'],
            ['name' => 'customers.view', 'title' => 'مشاهده مشتریان', 'group' => 'مشتریان'],
            ['name' => 'customers.create', 'title' => 'ایجاد مشتری', 'group' => 'مشتریان'],
            ['name' => 'customers.update', 'title' => 'ویرایش مشتری', 'group' => 'مشتریان'],
            ['name' => 'customers.delete', 'title' => 'حذف مشتری', 'group' => 'مشتریان'],
            ['name' => 'tasks.view', 'title' => 'مشاهده تسک‌ها', 'group' => 'تسک‌ها'],
            ['name' => 'tasks.create', 'title' => 'ایجاد تسک', 'group' => 'تسک‌ها'],
            ['name' => 'tasks.update', 'title' => 'ویرایش تسک', 'group' => 'تسک‌ها'],
            ['name' => 'tasks.delete', 'title' => 'حذف تسک', 'group' => 'تسک‌ها'],
            ['name' => 'tasks.assign', 'title' => 'ارجاع تسک به دیگران', 'group' => 'تسک‌ها'],
            ['name' => 'tasks.view_all', 'title' => 'مشاهده همه تسک‌ها', 'group' => 'تسک‌ها'],
            ['name' => 'tasks.update_status', 'title' => 'تغییر وضعیت تسک', 'group' => 'تسک‌ها'],
            ['name' => 'tickets.view', 'title' => 'مشاهده تیکت‌ها', 'group' => 'تیکت‌ها'],
            ['name' => 'tickets.view_all', 'title' => 'مشاهده همه تیکت‌ها', 'group' => 'تیکت‌ها'],
            ['name' => 'tickets.reply', 'title' => 'پاسخ به تیکت', 'group' => 'تیکت‌ها'],
            ['name' => 'tickets.assign', 'title' => 'ارجاع تیکت', 'group' => 'تیکت‌ها'],
            ['name' => 'tickets.update_status', 'title' => 'تغییر وضعیت تیکت', 'group' => 'تیکت‌ها'],
            ['name' => 'tickets.internal_notes', 'title' => 'ثبت یادداشت داخلی تیکت', 'group' => 'تیکت‌ها'],
            ['name' => 'tickets.convert_to_task', 'title' => 'تبدیل تیکت به تسک', 'group' => 'تیکت‌ها'],
            ['name' => 'tickets.delete', 'title' => 'حذف تیکت', 'group' => 'تیکت‌ها'],
            ['name' => 'finance.view', 'title' => 'مشاهده امور مالی مشتریان', 'group' => 'مالی'],
            ['name' => 'finance.create_entry', 'title' => 'ثبت سند بدهکار و بستانکار', 'group' => 'مالی'],
            ['name' => 'finance.void_entry', 'title' => 'ابطال سند مالی', 'group' => 'مالی'],
            ['name' => 'finance.review_payments', 'title' => 'بررسی و تأیید فیش‌های پرداخت', 'group' => 'مالی'],
            ['name' => 'finance.manage_bank_accounts', 'title' => 'مدیریت حساب‌های بانکی', 'group' => 'مالی'],
        ];

        foreach ($permissions as $permission) {
            Permission::query()->updateOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'web'],
                ['title' => $permission['title'], 'group' => $permission['group']],
            );
        }

        foreach ([
            ['name' => 'super-admin', 'title' => 'مدیر کل', 'is_system' => true],
            ['name' => 'project-manager', 'title' => 'مدیر پروژه', 'is_system' => false],
            ['name' => 'team-member', 'title' => 'عضو تیم', 'is_system' => false],
        ] as $roleData) {
            $role = Role::query()->updateOrCreate(
                ['name' => $roleData['name'], 'guard_name' => 'web'],
                ['title' => $roleData['title'], 'is_system' => $roleData['is_system']],
            );
            $role->syncPermissions(match ($role->name) {
                'super-admin' => Permission::all(),
                'project-manager' => [
                    'dashboard.view',
                    'customers.view',
                    'customers.create',
                    'customers.update',
                    'customers.delete',
                    'tasks.view',
                    'tasks.create',
                    'tasks.update',
                    'tasks.delete',
                    'tasks.assign',
                    'tasks.view_all',
                    'tasks.update_status',
                    'tickets.view',
                    'tickets.view_all',
                    'tickets.reply',
                    'tickets.assign',
                    'tickets.update_status',
                    'tickets.internal_notes',
                    'tickets.convert_to_task',
                    'tickets.delete',
                    'finance.view',
                    'finance.create_entry',
                    'finance.review_payments',
                ],
                default => [
                    'dashboard.view',
                    'tasks.view',
                    'tasks.create',
                    'tasks.update',
                    'tasks.update_status',
                    'tickets.view',
                    'tickets.reply',
                    'tickets.update_status',
                    'tickets.internal_notes',
                ],
            });
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
