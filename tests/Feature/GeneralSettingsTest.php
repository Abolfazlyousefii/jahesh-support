<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\GeneralSetting;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Settings\SettingsService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeneralSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('super-admin');
    }

    public function test_general_settings_route_is_protected(): void
    {
        $this->get('/settings/general')->assertRedirect('/login');

        $member = User::factory()->create(['is_active' => true]);
        $member->assignRole('team-member');

        $this->actingAs($member)->get('/settings/general')->assertForbidden();
        $this->actingAs($this->admin)->get('/settings/general')->assertOk();
    }

    public function test_super_admin_can_update_general_settings_and_activity_is_recorded(): void
    {
        $payload = [
            'company_name' => 'مجموعه تست',
            'app_name' => 'سامانه تست',
            'support_phone' => '09120000000',
            'support_hours' => '09:00 تا 21:00',
            'support_text' => 'پاسخ‌گویی تیم تست',
            'pagination_per_page' => 25,
            'portal_title' => 'پشتیبانی مجموعه تست',
            'portal_welcome_text' => 'به پنل تست خوش آمدید',
            'portal_show_support_phone' => '1',
            'portal_show_support_hours' => '0',
            'portal_active_ticket_limit' => 6,
        ];

        $this->actingAs($this->admin)
            ->put(route('settings.general.update'), $payload)
            ->assertRedirect();

        $settings = app(SettingsService::class);

        $this->assertSame('مجموعه تست', $settings->string('general.company_name'));
        $this->assertSame('سامانه تست', $settings->string('general.app_name'));
        $this->assertSame(25, $settings->paginationPerPage());
        $this->assertSame(6, $settings->portalActiveTicketLimit());
        $this->assertTrue($settings->boolean('portal.show_support_phone'));
        $this->assertFalse($settings->boolean('portal.show_support_hours'));

        $this->assertDatabaseHas('general_settings', [
            'key' => 'general.company_name',
            'value' => 'مجموعه تست',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'event' => 'settings.general_updated',
            'actor_id' => $this->admin->id,
        ]);
    }

    public function test_settings_are_reflected_in_customer_portal_layout(): void
    {
        app(SettingsService::class)->update([
            'general.company_name' => 'شرکت نمونه',
            'general.support_phone' => '09123334444',
            'general.support_hours' => '10 تا 18',
            'portal.title' => 'مرکز پشتیبانی نمونه',
            'portal.welcome_text' => 'سلام، به مرکز پشتیبانی خوش آمدید',
            'portal.show_support_phone' => true,
            'portal.show_support_hours' => true,
        ]);

        $customer = Customer::factory()->create(['is_active' => true]);

        $this->actingAs($customer, 'customer')
            ->get(route('portal.dashboard'))
            ->assertOk()
            ->assertSee('مرکز پشتیبانی نمونه')
            ->assertSee('سلام، به مرکز پشتیبانی خوش آمدید')
            ->assertSee('09123334444')
            ->assertSee('10 تا 18');
    }

    public function test_pagination_setting_is_used_by_customer_list(): void
    {
        app(SettingsService::class)->update([
            'general.pagination_per_page' => 10,
        ]);

        Customer::factory()->count(12)->create();

        $this->actingAs($this->admin)
            ->get(route('customers.index'))
            ->assertOk()
            ->assertViewHas('customers', fn ($customers) => $customers->perPage() === 10);
    }


    public function test_portal_active_ticket_limit_is_configurable(): void
    {
        app(SettingsService::class)->update([
            'portal.active_ticket_limit' => 3,
        ]);

        $customer = Customer::factory()->create(['is_active' => true]);
        Ticket::factory()->count(5)->for($customer)->create();

        $this->actingAs($customer, 'customer')
            ->get(route('portal.dashboard'))
            ->assertOk()
            ->assertViewHas('activeTickets', fn ($tickets) => $tickets->count() === 3)
            ->assertViewHas('activeTicketCount', 5);
    }

    public function test_general_settings_permission_is_registered_for_super_admin(): void
    {
        $permission = Permission::query()->where('name', 'settings.general.manage')->firstOrFail();
        $role = Role::query()->where('name', 'super-admin')->firstOrFail();

        $this->assertTrue($role->hasPermissionTo($permission));
    }
}
