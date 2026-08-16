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
            $role->syncPermissions($role->name === 'super-admin' ? Permission::all() : ['dashboard.view']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
