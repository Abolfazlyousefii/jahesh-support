<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('super-admin');
    }

    public function test_role_can_be_created(): void
    {
        $permission = Permission::findByName('team.view');

        $this->actingAs($this->admin)->post('/roles', [
            'name' => 'support-lead', 'title' => 'سرپرست پشتیبانی',
            'permission_ids' => [$permission->id],
        ])->assertRedirect('/roles');

        $role = Role::findByName('support-lead');
        $this->assertSame('سرپرست پشتیبانی', $role->title);
        $this->assertTrue($role->hasPermissionTo('team.view'));
    }

    public function test_role_permissions_can_be_synced(): void
    {
        $role = Role::findByName('project-manager');
        $permissions = Permission::whereIn('name', ['team.view', 'team.create'])->pluck('id')->all();

        $this->actingAs($this->admin)->put("/roles/{$role->id}", [
            'name' => $role->name, 'title' => $role->title, 'permission_ids' => $permissions,
        ])->assertRedirect('/roles');

        $this->assertTrue($role->fresh()->hasAllPermissions(['team.view', 'team.create']));
        $this->assertFalse($role->fresh()->hasPermissionTo('dashboard.view'));
    }

    public function test_super_admin_role_cannot_be_deleted(): void
    {
        $role = Role::findByName('super-admin');

        $this->actingAs($this->admin)->delete("/roles/{$role->id}")->assertForbidden();
        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }
}
