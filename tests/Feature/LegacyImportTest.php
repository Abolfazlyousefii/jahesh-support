<?php

namespace Tests\Feature;

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LegacyImportTest extends TestCase
{
    use RefreshDatabase;

    private string $legacyDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->legacyDatabase = storage_path('framework/testing/legacy-import.sqlite');
        @mkdir(dirname($this->legacyDatabase), 0777, true);
        @unlink($this->legacyDatabase);
        touch($this->legacyDatabase);

        config([
            'legacy-import.source' => 'test-legacy',
            'legacy-import.connection' => [
                'driver' => 'sqlite',
                'database' => $this->legacyDatabase,
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
            'database.connections.legacy_import' => [
                'driver' => 'sqlite',
                'database' => $this->legacyDatabase,
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);

        DB::purge('legacy_import');

        $this->createLegacySchema();
        $this->seedLegacyData();
    }

    protected function tearDown(): void
    {
        DB::disconnect('legacy_import');
        @unlink($this->legacyDatabase);

        parent::tearDown();
    }

    public function test_dry_run_does_not_write_any_legacy_data(): void
    {
        $this->artisan('legacy:import', ['--dry-run' => true])
            ->assertSuccessful();

        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('tasks', 0);
        $this->assertDatabaseCount('customer_ledger_entries', 0);
        $this->assertDatabaseCount('legacy_import_maps', 0);
    }

    public function test_import_moves_customers_staff_tasks_and_finance_idempotently(): void
    {
        $this->artisan('legacy:import', ['--force' => true])
            ->assertSuccessful();

        $this->assertDatabaseHas('customers', [
            'name' => 'مشتری تست',
            'company_name' => 'شرکت تست',
            'is_active' => 1,
        ]);
        $this->assertDatabaseHas('customer_phones', ['phone' => '09121234567', 'is_primary' => 1]);
        $this->assertDatabaseHas('users', ['phone' => '09121111111', 'name' => 'مدیر قدیمی']);
        $this->assertDatabaseHas('tasks', [
            'title' => 'تسک قدیمی',
            'status' => 'completed',
            'priority' => 'important',
        ]);
        $this->assertDatabaseHas('customer_ledger_entries', [
            'type' => 'debit',
            'amount' => 10000000,
            'source' => 'legacy_financial_debt',
        ]);

        $counts = [
            'customers' => DB::table('customers')->count(),
            'users' => DB::table('users')->count(),
            'tasks' => DB::table('tasks')->count(),
            'ledger' => DB::table('customer_ledger_entries')->count(),
        ];

        $this->artisan('legacy:import', ['--force' => true])
            ->assertSuccessful();

        $this->assertSame($counts['customers'], DB::table('customers')->count());
        $this->assertSame($counts['users'], DB::table('users')->count());
        $this->assertSame($counts['tasks'], DB::table('tasks')->count());
        $this->assertSame($counts['ledger'], DB::table('customer_ledger_entries')->count());
    }

    private function createLegacySchema(): void
    {
        Schema::connection('legacy_import')->create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('company_name')->nullable();
            $table->string('phone');
            $table->text('address')->nullable();
            $table->string('status');
            $table->timestamps();
        });

        Schema::connection('legacy_import')->create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('name');
            $table->string('phone');
            $table->string('role');
            $table->text('access_permissions')->nullable();
            $table->string('password');
            $table->string('remember_token')->nullable();
            $table->timestamps();
        });

        Schema::connection('legacy_import')->create('tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('ticket_id')->nullable();
            $table->unsignedBigInteger('seo_content_id')->nullable();
            $table->unsignedBigInteger('task_board_id')->nullable();
            $table->unsignedBigInteger('task_board_column_id')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('assigned_to');
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('start_date')->nullable();
            $table->timestamp('telegram_start_reminder_sent_at')->nullable();
            $table->dateTime('deadline')->nullable();
            $table->timestamp('telegram_deadline_reminder_sent_at')->nullable();
            $table->string('status');
            $table->string('priority');
            $table->unsignedInteger('position')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->unsignedBigInteger('seen_by')->nullable();
            $table->unsignedTinyInteger('progress')->default(0);
            $table->string('attachment_path')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('legacy_import')->create('financial_debts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('expense_type_id');
            $table->unsignedBigInteger('customer_expense_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('base_amount', 15, 2);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('final_amount', 15, 2);
            $table->date('registered_at');
            $table->date('due_date')->nullable();
            $table->string('period_key')->nullable();
            $table->string('status');
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('balance_amount', 15, 2)->default(0);
            $table->timestamp('settled_at')->nullable();
            $table->string('tracking_code')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('legacy_import')->create('financial_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('financial_debt_id');
            $table->unsignedBigInteger('created_by');
            $table->decimal('amount', 15, 2);
            $table->string('method')->nullable();
            $table->date('paid_at');
            $table->string('tracking_code')->nullable();
            $table->text('description')->nullable();
            $table->string('receipt_path')->nullable();
            $table->boolean('is_valid')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function seedLegacyData(): void
    {
        DB::connection('legacy_import')->table('customers')->insert([
            'id' => 6,
            'name' => 'مشتری تست',
            'company_name' => 'شرکت تست',
            'phone' => '09121234567',
            'address' => 'گرگان',
            'status' => 'active',
            'created_at' => '2026-07-29 00:54:48',
            'updated_at' => '2026-07-29 00:54:48',
        ]);

        DB::connection('legacy_import')->table('users')->insert([
            [
                'id' => 1,
                'customer_id' => null,
                'name' => 'مدیر قدیمی',
                'phone' => '09121111111',
                'role' => 'admin',
                'access_permissions' => null,
                'password' => bcrypt('password'),
                'remember_token' => null,
                'created_at' => '2026-07-26 02:05:03',
                'updated_at' => '2026-07-26 02:05:03',
            ],
            [
                'id' => 15,
                'customer_id' => 6,
                'name' => 'مشتری تست',
                'phone' => '09121234567',
                'role' => 'customer',
                'access_permissions' => null,
                'password' => bcrypt('password'),
                'remember_token' => null,
                'created_at' => '2026-07-29 00:54:48',
                'updated_at' => '2026-07-29 00:54:48',
            ],
        ]);

        DB::connection('legacy_import')->table('tasks')->insert([
            'id' => 10,
            'project_id' => null,
            'customer_id' => 6,
            'ticket_id' => null,
            'seo_content_id' => null,
            'task_board_id' => null,
            'task_board_column_id' => null,
            'created_by' => 1,
            'assigned_to' => 1,
            'title' => 'تسک قدیمی',
            'description' => 'توضیحات',
            'start_date' => '2026-07-29 10:00:00',
            'telegram_start_reminder_sent_at' => null,
            'deadline' => '2026-07-31 23:59:00',
            'telegram_deadline_reminder_sent_at' => null,
            'status' => 'done',
            'priority' => 'high',
            'position' => 0,
            'completed_at' => '2026-07-30 10:00:00',
            'resolved_at' => null,
            'closed_at' => null,
            'first_seen_at' => null,
            'last_seen_at' => null,
            'seen_by' => null,
            'progress' => 100,
            'attachment_path' => null,
            'archived_at' => null,
            'created_at' => '2026-07-29 09:00:00',
            'updated_at' => '2026-07-30 10:00:00',
            'deleted_at' => null,
        ]);

        DB::connection('legacy_import')->table('financial_debts')->insert([
            'id' => 9,
            'customer_id' => 6,
            'project_id' => 10,
            'expense_type_id' => 1,
            'customer_expense_id' => 9,
            'created_by' => 1,
            'title' => 'هزینه اولیه پروژه',
            'description' => null,
            'base_amount' => 10000000,
            'discount' => 0,
            'tax' => 0,
            'final_amount' => 10000000,
            'registered_at' => '2026-07-29',
            'due_date' => '2026-07-29',
            'period_key' => 'one-time',
            'status' => 'unpaid',
            'paid_amount' => 0,
            'balance_amount' => 10000000,
            'settled_at' => null,
            'tracking_code' => null,
            'cancelled_by' => null,
            'cancelled_at' => null,
            'cancel_reason' => null,
            'created_at' => '2026-07-29 00:58:36',
            'updated_at' => '2026-07-29 00:58:36',
            'deleted_at' => null,
        ]);
    }
}
