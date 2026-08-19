<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $permissionId = DB::table('permissions')->where([
            'name' => 'activity.view',
            'guard_name' => 'web',
        ])->value('id');

        if (! $permissionId) {
            $permissionId = DB::table('permissions')->insertGetId([
                'name' => 'activity.view',
                'guard_name' => 'web',
                'title' => 'مشاهده گزارش فعالیت‌ها',
                'group' => 'گزارش فعالیت‌ها',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $superAdminId = DB::table('roles')->where([
            'name' => 'super-admin',
            'guard_name' => 'web',
        ])->value('id');

        if ($superAdminId && Schema::hasTable('role_has_permissions')) {
            DB::table('role_has_permissions')->updateOrInsert([
                'permission_id' => $permissionId,
                'role_id' => $superAdminId,
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $permissionId = DB::table('permissions')->where([
            'name' => 'activity.view',
            'guard_name' => 'web',
        ])->value('id');

        if ($permissionId && Schema::hasTable('role_has_permissions')) {
            DB::table('role_has_permissions')->where('permission_id', $permissionId)->delete();
        }

        if ($permissionId && Schema::hasTable('model_has_permissions')) {
            DB::table('model_has_permissions')->where('permission_id', $permissionId)->delete();
        }

        if ($permissionId) {
            DB::table('permissions')->where('id', $permissionId)->delete();
        }
    }
};
