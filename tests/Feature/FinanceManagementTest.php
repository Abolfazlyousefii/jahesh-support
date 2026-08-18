<?php

namespace Tests\Feature;

use App\Enums\LedgerEntryType;
use App\Enums\PaymentReceiptStatus;
use App\Models\Customer;
use App\Models\CustomerLedgerEntry;
use App\Models\CustomerPaymentReceipt;
use App\Models\FinancialBankAccount;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FinanceManagementTest extends TestCase
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

    public function test_finance_routes_are_permission_protected(): void
    {
        $customer = Customer::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->get('/finance')->assertForbidden();
        $this->actingAs($user)->get("/finance/customers/{$customer->id}")->assertForbidden();
        $this->actingAs($this->admin)->get('/finance')->assertOk();
    }

    public function test_project_manager_receives_operational_finance_permissions_but_not_bank_settings(): void
    {
        $role = Role::findByName('project-manager');

        $this->assertTrue($role->hasAllPermissions([
            'finance.view',
            'finance.create_entry',
            'finance.review_payments',
        ]));
        $this->assertFalse($role->hasPermissionTo('finance.void_entry'));
        $this->assertFalse($role->hasPermissionTo('finance.manage_bank_accounts'));
    }

    public function test_authorized_user_can_create_debit_and_credit_entries(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($this->admin)->post("/finance/customers/{$customer->id}/entries", [
            'type' => LedgerEntryType::Debit->value,
            'amount' => '۱۵,۰۰۰,۰۰۰',
            'description' => 'هزینه طراحی سایت',
            'entry_date' => today()->format('Y-m-d'),
        ])->assertRedirect();

        $this->actingAs($this->admin)->post("/finance/customers/{$customer->id}/entries", [
            'type' => LedgerEntryType::Credit->value,
            'amount' => '5,000,000',
            'description' => 'پرداخت مشتری',
            'entry_date' => today()->format('Y-m-d'),
        ])->assertRedirect();

        $this->assertDatabaseHas('customer_ledger_entries', [
            'customer_id' => $customer->id,
            'type' => 'debit',
            'amount' => 15000000,
        ]);
        $this->actingAs($this->admin)->get("/finance/customers/{$customer->id}")
            ->assertOk()
            ->assertSee('10,000,000');
    }

    public function test_voided_entry_remains_in_history_but_no_longer_affects_balance(): void
    {
        $customer = Customer::factory()->create();
        $entry = CustomerLedgerEntry::query()->create([
            'customer_id' => $customer->id,
            'type' => LedgerEntryType::Debit,
            'amount' => 7000000,
            'description' => 'سند اشتباه',
            'entry_date' => today(),
            'source' => 'manual',
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)->patch("/finance/entries/{$entry->id}/void", [
            'void_reason' => 'ثبت اشتباه مبلغ',
        ])->assertRedirect();

        $this->assertDatabaseHas('customer_ledger_entries', [
            'id' => $entry->id,
            'amount' => 7000000,
            'void_reason' => 'ثبت اشتباه مبلغ',
        ]);
        $this->assertNotNull($entry->fresh()->voided_at);
        $this->actingAs($this->admin)->get("/finance/customers/{$customer->id}")
            ->assertOk()
            ->assertSee('حساب تسویه است');
    }

    public function test_approving_receipt_is_idempotent_and_creates_exactly_one_credit_entry(): void
    {
        $customer = Customer::factory()->create();
        $account = $this->bankAccount();
        $receipt = $this->receipt($customer, $account, 4500000);

        $this->actingAs($this->admin)->patch("/finance/receipts/{$receipt->id}/approve")
            ->assertRedirect();

        $this->assertSame(PaymentReceiptStatus::Approved, $receipt->fresh()->status);
        $this->assertDatabaseHas('customer_ledger_entries', [
            'customer_id' => $customer->id,
            'type' => LedgerEntryType::Credit->value,
            'amount' => 4500000,
            'payment_receipt_id' => $receipt->id,
        ]);
        $this->assertDatabaseCount('customer_ledger_entries', 1);

        $this->actingAs($this->admin)->patch("/finance/receipts/{$receipt->id}/approve")
            ->assertSessionHasErrors('receipt');
        $this->assertDatabaseCount('customer_ledger_entries', 1);
    }

    public function test_rejecting_receipt_does_not_create_ledger_entry(): void
    {
        $customer = Customer::factory()->create();
        $receipt = $this->receipt($customer, $this->bankAccount(), 3000000);

        $this->actingAs($this->admin)->patch("/finance/receipts/{$receipt->id}/reject", [
            'rejection_reason' => 'تصویر فیش خوانا نیست',
        ])->assertRedirect();

        $this->assertSame(PaymentReceiptStatus::Rejected, $receipt->fresh()->status);
        $this->assertDatabaseCount('customer_ledger_entries', 0);
    }

    public function test_receipt_file_is_served_only_through_protected_route(): void
    {
        Storage::fake('local');
        $customer = Customer::factory()->create();
        $receipt = $this->receipt($customer, $this->bankAccount(), 1000000);
        Storage::disk('local')->put($receipt->receipt_path, 'fake-receipt');

        $this->get("/finance/receipts/{$receipt->id}/file")->assertRedirect('/login');
        $this->actingAs($this->admin)->get("/finance/receipts/{$receipt->id}/file")->assertOk();
    }

    private function bankAccount(): FinancialBankAccount
    {
        return FinancialBankAccount::query()->create([
            'bank_name' => 'ملت',
            'account_holder' => 'تیم جهش',
            'card_number' => '6104337000000000',
            'is_active' => true,
        ]);
    }

    private function receipt(Customer $customer, FinancialBankAccount $account, int $amount): CustomerPaymentReceipt
    {
        return CustomerPaymentReceipt::query()->create([
            'customer_id' => $customer->id,
            'bank_account_id' => $account->id,
            'amount' => $amount,
            'paid_at' => today(),
            'tracking_code' => '12345',
            'receipt_path' => "finance/receipts/{$customer->id}/test.pdf",
            'original_name' => 'test.pdf',
            'mime_type' => 'application/pdf',
            'status' => PaymentReceiptStatus::Pending,
        ]);
    }
}
