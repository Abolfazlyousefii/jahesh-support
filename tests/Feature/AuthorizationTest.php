<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_user_without_permission_receives_403(): void
    {
        $this->actingAs(User::factory()->create())->get('/team')->assertForbidden();
    }

    public function test_user_with_permission_can_access_protected_route(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('team.view');

        $this->actingAs($user)->get('/team')->assertOk()->assertSee('اعضای تیم');
    }

    public function test_super_admin_has_full_backend_access(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $this->actingAs($user)->get('/team')->assertOk();
        $this->actingAs($user)->get('/roles')->assertOk();
    }
}
