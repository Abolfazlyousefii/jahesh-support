<?php

namespace Tests\Feature;

use App\Enums\LedgerEntryType;
use App\Enums\PaymentReceiptStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\TicketStatus;
use App\Models\Customer;
use App\Models\CustomerLedgerEntry;
use App\Models\CustomerPaymentReceipt;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardOperationalTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_dashboard_surfaces_real_operational_attention_items(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create(['name' => 'مدیر تست']);
        $admin->assignRole('super-admin');
        $customer = Customer::factory()->create(['name' => 'مشتری داشبورد']);

        Ticket::factory()->create([
            'customer_id' => $customer->id,
            'subject' => 'تیکت بدون مسئول داشبورد',
            'assigned_to' => null,
            'status' => TicketStatus::New,
            'last_customer_message_at' => now(),
            'last_staff_message_at' => null,
        ]);

        Task::factory()->create([
            'title' => 'تسک معوق داشبورد',
            'assignee_id' => $admin->id,
            'created_by' => $admin->id,
            'priority' => TaskPriority::Urgent,
            'status' => TaskStatus::InProgress,
            'due_date' => today()->subDay(),
        ]);

        CustomerPaymentReceipt::query()->create([
            'customer_id' => $customer->id,
            'amount' => 5000000,
            'paid_at' => today(),
            'receipt_path' => 'receipts/test.jpg',
            'status' => PaymentReceiptStatus::Pending,
        ]);

        CustomerLedgerEntry::query()->create([
            'customer_id' => $customer->id,
            'type' => LedgerEntryType::Debit,
            'amount' => 12000000,
            'description' => 'بدهی تست داشبورد',
            'entry_date' => today(),
            'source' => 'manual',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            // Stable UI contracts: these markers survive wording / visual redesigns.
            ->assertSee('data-testid="dashboard-action-items"', false)
            ->assertSee('data-testid="dashboard-ticket-unassigned" data-count="1"', false)
            ->assertSee('data-testid="dashboard-finance-pending" data-count="1"', false)
            ->assertSee('data-testid="dashboard-finance-panel"', false)
            // Real seeded operational items must still be surfaced.
            ->assertSee('تیکت بدون مسئول داشبورد')
            ->assertSee('تسک معوق داشبورد')
            ->assertSee('مشتری داشبورد');
    }
}
