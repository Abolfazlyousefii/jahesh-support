<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_super_admin_can_view_activity_log_and_regular_member_cannot(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('super-admin');

        $member = User::factory()->create(['is_active' => true]);
        $member->assignRole('team-member');

        $this->actingAs($admin)
            ->get(route('activity.index'))
            ->assertOk();

        $this->actingAs($member)
            ->get(route('activity.index'))
            ->assertForbidden();
    }

    public function test_customer_create_update_and_delete_are_audited_without_password_values(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('super-admin');

        $payload = [
            'name' => 'مشتری تست',
            'company_name' => 'شرکت تست',
            'city' => 'گرگان',
            'address' => 'آدرس تست',
            'notes' => null,
            'is_active' => '1',
            'phones' => [['phone' => '09111111111']],
            'primary_phone' => 0,
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
        ];

        $this->actingAs($admin)
            ->post(route('customers.store'), $payload)
            ->assertRedirect();

        $customer = Customer::query()->firstOrFail();

        $created = ActivityLog::query()->where('event', 'customer.created')->firstOrFail();
        $this->assertSame($admin->id, $created->actor_id);
        $this->assertSame($customer->id, $created->subject_id);
        $this->assertArrayNotHasKey('password', $created->new_values ?? []);

        $payload['name'] = 'مشتری ویرایش شده';
        $payload['password'] = 'AnotherSecure123';
        $payload['password_confirmation'] = 'AnotherSecure123';

        $this->actingAs($admin)
            ->put(route('customers.update', $customer), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('activity_logs', [
            'event' => 'customer.updated',
            'subject_id' => $customer->id,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'event' => 'customer.password_changed_by_admin',
            'subject_id' => $customer->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('customers.destroy', $customer))
            ->assertRedirect();

        $this->assertDatabaseHas('activity_logs', [
            'event' => 'customer.deleted',
            'subject_id' => $customer->id,
        ]);

        $this->assertFalse(
            ActivityLog::query()->get()->contains(fn (ActivityLog $log) =>
                str_contains(json_encode([$log->old_values, $log->new_values, $log->metadata]), 'SecurePass123')
            )
        );
    }

    public function test_activity_permission_is_registered_for_super_admin(): void
    {
        $permission = Permission::query()->where('name', 'activity.view')->firstOrFail();
        $role = Role::query()->where('name', 'super-admin')->firstOrFail();

        $this->assertTrue($role->hasPermissionTo($permission));
    }
    public function test_activity_filters_use_persian_date_picker(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('super-admin');

        $this->actingAs($admin)
            ->get(route('activity.index', [
                'from' => '2026-08-01',
                'to' => '2026-08-19',
            ]))
            ->assertOk()
            ->assertSee('۱۴۰۵/۰۵/', false)
            ->assertDontSee('type="date"', false);
    }

    public function test_activity_date_picker_is_not_clipped_by_filter_panel(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('super-admin');

        $this->actingAs($admin)
            ->get(route('activity.index'))
            ->assertOk()
            ->assertSee('panel relative overflow-visible', false)
            ->assertSee('xl:grid-cols-12', false)
            ->assertSee('xl:col-span-3', false)
            ->assertDontSee('panel overflow-hidden', false);
    }

}
