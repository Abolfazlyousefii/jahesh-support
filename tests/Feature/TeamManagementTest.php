<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamManagementTest extends TestCase
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

    public function test_authorized_user_can_create_member_with_normalized_phone_and_roles(): void
    {
        $role = Role::findByName('team-member');

        $this->actingAs($this->admin)->post('/team', [
            'name' => 'عضو جدید',
            'phone' => '+۹۸۹۱۲۳۴۵۶۷۸۹',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
            'role_ids' => [$role->id],
            'is_active' => '1',
        ])->assertRedirect('/team');

        $user = User::where('phone', '09123456789')->firstOrFail();
        $this->assertTrue($user->hasRole('team-member'));
        $this->assertTrue($user->is_active);
    }

    public function test_duplicate_phone_is_rejected(): void
    {
        User::factory()->create(['phone' => '09123456789']);

        $this->actingAs($this->admin)->post('/team', [
            'name' => 'عضو تکراری', 'phone' => '09123456789',
            'password' => 'secure-password', 'password_confirmation' => 'secure-password',
            'is_active' => '1',
        ])->assertSessionHasErrors('phone');
    }

    public function test_authorized_user_can_update_member_without_changing_password(): void
    {
        $user = User::factory()->create(['phone' => '09121111111', 'password' => 'old-password']);
        $role = Role::findByName('project-manager');

        $this->actingAs($this->admin)->put("/team/{$user->id}", [
            'name' => 'نام ویرایش‌شده', 'phone' => '9122222222',
            'role_ids' => [$role->id], 'is_active' => '1',
        ])->assertRedirect('/team');

        $user->refresh();
        $this->assertSame('09122222222', $user->phone);
        $this->assertTrue($user->hasRole('project-manager'));
        $this->assertTrue(password_verify('old-password', $user->password));
    }

    public function test_last_active_super_admin_cannot_be_deleted_or_deactivated(): void
    {
        $superRole = Role::findByName('super-admin');

        $this->actingAs($this->admin)->delete("/team/{$this->admin->id}")->assertForbidden();
        $this->assertNotSoftDeleted($this->admin);

        $this->actingAs($this->admin)->put("/team/{$this->admin->id}", [
            'name' => $this->admin->name, 'phone' => $this->admin->phone,
            'role_ids' => [$superRole->id],
        ])->assertForbidden();
        $this->assertTrue($this->admin->fresh()->is_active);
    }
}
