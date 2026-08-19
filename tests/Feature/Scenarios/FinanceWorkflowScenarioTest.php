<?php

namespace Tests\Feature\Scenarios;

use App\Enums\PaymentReceiptStatus;
use App\Models\CustomerPaymentReceipt;
use App\Models\FinancialBankAccount;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Group;

#[Group('release')]
class FinanceWorkflowScenarioTest extends ScenarioTestCase
{
    public function test_debt_receipt_approval_rejection_and_balance_work_as_one_financial_flow(): void
    {
        Storage::fake('local');

        $customer = $this->customerWithPhone('09350000041');
        $bank = FinancialBankAccount::query()->create([
            'bank_name' => 'ملت',
            'account_holder' => 'تیم جهش',
            'card_number' => '6104337000000000',
            'is_active' => true,
        ]);

        // مدیر پروژه 10 میلیون تومان بدهی ثبت می‌کند.
        $this->actingAs($this->manager)->post("/finance/customers/{$customer->id}/entries", [
            'type' => 'debit',
            'amount' => '۱۰,۰۰۰,۰۰۰',
            'description' => 'بدهی سناریوی مالی',
            'entry_date' => today()->format('Y-m-d'),
        ])->assertRedirect();

        $this->assertSame(10000000, $this->effectiveBalance($customer));

        // مشتری فیش 4 میلیون ثبت می‌کند؛ Pending نباید مانده را تغییر دهد.
        $this->actingAs($customer, 'customer')->post('/portal/finance/receipts', [
            'bank_account_id' => $bank->id,
            'amount' => '۴,۰۰۰,۰۰۰',
            'paid_at' => today()->format('Y-m-d'),
            'tracking_code' => 'SCENARIO-4000',
            'receipt' => UploadedFile::fake()->create('scenario-approved.pdf', 100, 'application/pdf'),
        ])->assertRedirect('/portal/finance');

        $approvedReceipt = CustomerPaymentReceipt::query()
            ->where('tracking_code', 'SCENARIO-4000')
            ->firstOrFail();

        $this->assertSame(PaymentReceiptStatus::Pending, $approvedReceipt->status);
        $this->assertSame(10000000, $this->effectiveBalance($customer));
        $this->assertDatabaseCount('customer_ledger_entries', 1);

        // تأیید فیش دقیقاً یک Credit ایجاد می‌کند.
        $this->actingAs($this->manager)->patch("/finance/receipts/{$approvedReceipt->id}/approve")
            ->assertRedirect();

        $approvedReceipt->refresh();
        $this->assertSame(PaymentReceiptStatus::Approved, $approvedReceipt->status);
        $this->assertSame(6000000, $this->effectiveBalance($customer));
        $this->assertDatabaseHas('customer_ledger_entries', [
            'customer_id' => $customer->id,
            'type' => 'credit',
            'amount' => 4000000,
            'payment_receipt_id' => $approvedReceipt->id,
        ]);
        $this->assertDatabaseCount('customer_ledger_entries', 2);

        // Approve مجدد نباید Credit دوم بسازد.
        $this->actingAs($this->manager)->patch("/finance/receipts/{$approvedReceipt->id}/approve")
            ->assertSessionHasErrors('receipt');
        $this->assertDatabaseCount('customer_ledger_entries', 2);
        $this->assertSame(6000000, $this->effectiveBalance($customer));

        // فیش دوم رد می‌شود و هیچ اثر حسابداری ندارد.
        $this->actingAs($customer, 'customer')->post('/portal/finance/receipts', [
            'bank_account_id' => $bank->id,
            'amount' => '۲,۰۰۰,۰۰۰',
            'paid_at' => today()->format('Y-m-d'),
            'tracking_code' => 'SCENARIO-2000',
            'receipt' => UploadedFile::fake()->create('scenario-rejected.pdf', 100, 'application/pdf'),
        ])->assertRedirect('/portal/finance');

        $rejectedReceipt = CustomerPaymentReceipt::query()
            ->where('tracking_code', 'SCENARIO-2000')
            ->firstOrFail();

        $this->actingAs($this->manager)->patch("/finance/receipts/{$rejectedReceipt->id}/reject", [
            'rejection_reason' => 'فیش سناریویی برای تست رد شده است.',
        ])->assertRedirect();

        $this->assertSame(PaymentReceiptStatus::Rejected, $rejectedReceipt->fresh()->status);
        $this->assertDatabaseCount('customer_ledger_entries', 2);
        $this->assertSame(6000000, $this->effectiveBalance($customer));

        // عدد نهایی باید برای خود مشتری نیز همین مقدار باشد.
        $this->actingAs($customer, 'customer')->get('/portal/finance')
            ->assertOk()
            ->assertSee('6,000,000');
    }
}
