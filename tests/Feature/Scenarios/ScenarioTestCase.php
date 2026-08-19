<?php

namespace Tests\Feature\Scenarios;

use App\Models\Customer;
use App\Models\CustomerPhone;
use App\Models\SmsSetting;
use App\Models\User;
use App\Services\Sms\SmsPatternCatalog;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

abstract class ScenarioTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $manager;

    protected User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create([
            'name' => 'مدیر کل سناریو',
            'phone' => '09120000001',
        ]);
        $this->admin->assignRole('super-admin');

        $this->manager = User::factory()->create([
            'name' => 'مدیر پروژه سناریو',
            'phone' => '09120000002',
        ]);
        $this->manager->assignRole('project-manager');

        $this->member = User::factory()->create([
            'name' => 'عضو تیم سناریو',
            'phone' => '09120000003',
        ]);
        $this->member->assignRole('team-member');

        // تست‌های سناریویی به شبکه واقعی یا ملی پیامک وابسته نیستند.
        // هر تستی که رفتار SMS را بررسی کند، خودش تنظیمات Fake/Queue را فعال می‌کند.
        if (class_exists(SmsPatternCatalog::class)) {
            SmsPatternCatalog::ensureStored();
        }

        if (class_exists(SmsSetting::class)) {
            SmsSetting::current()->update(['enabled' => false]);
        }
    }

    protected function customerWithPhone(
        string $phone = '09350000001',
        ?string $password = 'CustomerPass123',
        bool $active = true,
    ): Customer {
        $customer = Customer::factory()->create([
            'name' => 'مشتری سناریو',
            'company_name' => 'شرکت سناریو',
            'is_active' => $active,
        ]);

        CustomerPhone::factory()->for($customer)->create([
            'phone' => $phone,
            'is_primary' => true,
        ]);

        if ($password !== null) {
            $customer->forceFill([
                'password' => Hash::make($password),
                'password_changed_at' => now(),
            ])->save();
        }

        return $customer->refresh();
    }

    protected function effectiveBalance(Customer $customer): int
    {
        $debit = (int) $customer->ledgerEntries()
            ->effective()
            ->where('type', 'debit')
            ->sum('amount');

        $credit = (int) $customer->ledgerEntries()
            ->effective()
            ->where('type', 'credit')
            ->sum('amount');

        return $debit - $credit;
    }
}
