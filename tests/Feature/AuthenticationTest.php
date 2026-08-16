<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_guest_cannot_see_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_active_user_can_login_with_phone_and_password(): void
    {
        $user = User::factory()->create(['phone' => '09123456789', 'password' => 'secret-pass']);
        $user->givePermissionTo('dashboard.view');

        $this->post('/login', ['phone' => '09123456789', 'password' => 'secret-pass'])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_wrong_password_is_rejected(): void
    {
        User::factory()->create(['phone' => '09123456789', 'password' => 'secret-pass']);

        $this->post('/login', ['phone' => '09123456789', 'password' => 'wrong-pass'])
            ->assertSessionHasErrors('phone');
        $this->assertGuest();
    }

    public function test_persian_phone_digits_are_normalized_during_login(): void
    {
        $user = User::factory()->create(['phone' => '09123456789', 'password' => 'secret-pass']);
        $user->givePermissionTo('dashboard.view');

        $this->post('/login', ['phone' => '۰۹۱۲۳۴۵۶۷۸۹', 'password' => 'secret-pass'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_inactive_user_cannot_login_or_keep_using_session(): void
    {
        $inactive = User::factory()->inactive()->create(['phone' => '09123456789', 'password' => 'secret-pass']);
        $inactive->givePermissionTo('dashboard.view');

        $this->post('/login', ['phone' => '09123456789', 'password' => 'secret-pass'])
            ->assertSessionHasErrors('phone');
        $this->actingAs($inactive)->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }
}
