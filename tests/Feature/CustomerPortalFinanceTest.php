<?php

namespace Tests\Feature;

use App\Enums\LedgerEntryType;
use App\Enums\PaymentReceiptStatus;
use App\Models\Customer;
use App\Models\CustomerLedgerEntry;
use App\Models\CustomerPaymentReceipt;
use App\Models\FinancialBankAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomerPortalFinanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_sees_only_own_financial_history(): void
    {
        $customer = Customer::factory()->create();
        $other = Customer::factory()->create();
        $this->entry($customer, 9000000, 'سند مشتری اصلی');
        $this->entry($other, 4000000, 'سند مشتری دیگر');

        auth('customer')->login($customer);

        $this->get('/portal/finance')
            ->assertOk()
            ->assertSee('سند مشتری اصلی')
            ->assertDontSee('سند مشتری دیگر');
    }

    public function test_customer_can_submit_receipt_but_pending_payment_does_not_change_balance(): void
    {
        Storage::fake('local');
        $customer = Customer::factory()->create();
        $this->entry($customer, 10000000, 'بدهی خدمات');
        $account = $this->bankAccount();
        auth('customer')->login($customer);

        $this->post('/portal/finance/receipts', [
            'bank_account_id' => $account->id,
            'amount' => '۴,۰۰۰,۰۰۰',
            'paid_at' => today()->format('Y-m-d'),
            'tracking_code' => '۱۲۳۴۵',
            'receipt' => UploadedFile::fake()->create('fish.pdf', 120, 'application/pdf'),
        ])->assertRedirect('/portal/finance');

        $receipt = CustomerPaymentReceipt::query()->firstOrFail();
        $this->assertSame(PaymentReceiptStatus::Pending, $receipt->status);
        $this->assertSame(4000000, $receipt->amount);
        Storage::disk('local')->assertExists($receipt->receipt_path);
        $this->assertDatabaseCount('customer_ledger_entries', 1);

        $this->get('/portal/finance')->assertOk()->assertSee('10,000,000');
    }

    public function test_customer_cannot_submit_to_inactive_bank_account(): void
    {
        Storage::fake('local');
        $customer = Customer::factory()->create();
        $account = $this->bankAccount(false);
        auth('customer')->login($customer);

        $this->post('/portal/finance/receipts', [
            'bank_account_id' => $account->id,
            'amount' => 1000000,
            'paid_at' => today()->format('Y-m-d'),
            'receipt' => UploadedFile::fake()->create('fish.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('bank_account_id');

        $this->assertDatabaseCount('customer_payment_receipts', 0);
    }

    public function test_customer_cannot_open_another_customers_receipt_file(): void
    {
        Storage::fake('local');
        $customer = Customer::factory()->create();
        $other = Customer::factory()->create();
        $receipt = CustomerPaymentReceipt::query()->create([
            'customer_id' => $other->id,
            'bank_account_id' => $this->bankAccount()->id,
            'amount' => 1000000,
            'paid_at' => today(),
            'receipt_path' => 'finance/receipts/other.pdf',
            'mime_type' => 'application/pdf',
            'status' => PaymentReceiptStatus::Pending,
        ]);
        Storage::disk('local')->put($receipt->receipt_path, 'private');
        auth('customer')->login($customer);

        $this->get("/portal/finance/receipts/{$receipt->id}/file")->assertNotFound();
    }

    public function test_approved_credit_is_visible_in_customer_balance(): void
    {
        $customer = Customer::factory()->create();
        $this->entry($customer, 10000000, 'بدهی', LedgerEntryType::Debit);
        $this->entry($customer, 3000000, 'واریز تأییدشده', LedgerEntryType::Credit);
        auth('customer')->login($customer);

        $this->get('/portal/finance')->assertOk()->assertSee('7,000,000');
    }

    private function entry(Customer $customer, int $amount, string $description, LedgerEntryType $type = LedgerEntryType::Debit): CustomerLedgerEntry
    {
        return CustomerLedgerEntry::query()->create([
            'customer_id' => $customer->id,
            'type' => $type,
            'amount' => $amount,
            'description' => $description,
            'entry_date' => today(),
            'source' => 'manual',
        ]);
    }

    private function bankAccount(bool $active = true): FinancialBankAccount
    {
        return FinancialBankAccount::query()->create([
            'bank_name' => 'ملت',
            'account_holder' => 'تیم جهش',
            'card_number' => '6104337000000000',
            'is_active' => $active,
        ]);
    }
}
