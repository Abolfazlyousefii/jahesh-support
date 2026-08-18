<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $permissions = [
            ['name' => 'finance.view', 'title' => 'مشاهده امور مالی مشتریان', 'group' => 'مالی'],
            ['name' => 'finance.create_entry', 'title' => 'ثبت سند بدهکار و بستانکار', 'group' => 'مالی'],
            ['name' => 'finance.void_entry', 'title' => 'ابطال سند مالی', 'group' => 'مالی'],
            ['name' => 'finance.review_payments', 'title' => 'بررسی و تأیید فیش‌های پرداخت', 'group' => 'مالی'],
            ['name' => 'finance.manage_bank_accounts', 'title' => 'مدیریت حساب‌های بانکی', 'group' => 'مالی'],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission['name'], 'guard_name' => 'web'],
                [
                    'title' => $permission['title'],
                    'group' => $permission['group'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }

        $this->attachPermissions('super-admin', array_column($permissions, 'name'));
        $this->attachPermissions('project-manager', [
            'finance.view',
            'finance.create_entry',
            'finance.review_payments',
        ]);
    }

    public function down(): void
    {
        $names = [
            'finance.view',
            'finance.create_entry',
            'finance.void_entry',
            'finance.review_payments',
            'finance.manage_bank_accounts',
        ];

        $ids = DB::table('permissions')->where('guard_name', 'web')->whereIn('name', $names)->pluck('id');
        DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }

    /** @param array<int,string> $permissionNames */
    private function attachPermissions(string $roleName, array $permissionNames): void
    {
        $roleId = DB::table('roles')->where('name', $roleName)->where('guard_name', 'web')->value('id');

        if (! $roleId) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->where('guard_name', 'web')
            ->whereIn('name', $permissionNames)
            ->pluck('id');

        foreach ($permissionIds as $permissionId) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }
    }
};
