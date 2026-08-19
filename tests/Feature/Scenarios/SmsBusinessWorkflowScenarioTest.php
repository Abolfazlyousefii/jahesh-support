<?php

namespace Tests\Feature\Scenarios;

use App\Enums\TaskStatus;
use App\Enums\TicketStatus;
use App\Models\CustomerPaymentReceipt;
use App\Models\FinancialBankAccount;
use App\Models\SmsLog;
use App\Models\SmsPattern;
use App\Models\SmsSetting;
use App\Models\Task;
use App\Models\Ticket;
use App\Services\Sms\SmsPatternCatalog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Group;

#[Group('release')]
class SmsBusinessWorkflowScenarioTest extends ScenarioTestCase
{
    public function test_business_events_create_expected_sms_jobs_without_calling_real_provider(): void
    {
        Queue::fake();
        Storage::fake('local');

        $customer = $this->customerWithPhone('09350000051');

        SmsPatternCatalog::ensureStored();
        SmsSetting::current()->update([
            'enabled' => true,
            'webservice_username' => 'release-test-user',
            'webservice_password' => 'ReleaseTestPass123',
            'internal_recipient_user_ids' => [$this->manager->id],
        ]);

        foreach (array_keys(SmsPatternCatalog::definitions()) as $index => $key) {
            SmsPattern::query()->where('key', $key)->update([
                'enabled' => true,
                'body_id' => 10000 + $index,
            ]);
        }

        // ثبت تیکت: پیام مشتری + اعلان تیم.
        $this->actingAs($customer, 'customer')->post('/portal/tickets', [
            'subject' => 'سناریو پیامکی تیکت',
            'priority' => 'important',
            'description' => 'تیکت برای بررسی زنجیره پیامک.',
        ])->assertRedirect();

        $ticket = Ticket::query()->where('subject', 'سناریو پیامکی تیکت')->firstOrFail();
        $this->assertSmsPatternQueued('ticket_created_customer', $customer->primaryPhone()->value('phone'));
        $this->assertSmsPatternQueued('ticket_created_staff', $this->manager->phone);

        // ارجاع به عضو تیم.
        $this->actingAs($this->manager)->patch("/tickets/{$ticket->id}/assignment", [
            'assignee_id' => $this->member->id,
        ])->assertRedirect();
        $this->assertSmsPatternQueued('ticket_assigned', $this->member->phone);

        // پاسخ تیم به مشتری.
        $this->actingAs($this->member)->post("/tickets/{$ticket->id}/reply", [
            'body' => 'پاسخ سناریوی پیامکی از تیم.',
            'after_reply_status' => TicketStatus::WaitingCustomer->value,
        ])->assertRedirect();
        $this->assertSmsPatternQueued('ticket_staff_reply', $customer->primaryPhone()->value('phone'));

        // پاسخ مشتری به مسئول.
        $this->actingAs($customer, 'customer')->post("/portal/tickets/{$ticket->id}/replies", [
            'body' => 'پاسخ سناریوی پیامکی مشتری.',
        ])->assertRedirect();
        $this->assertSmsPatternQueued('ticket_customer_reply', $this->member->phone);

        // تبدیل به Task و اعلان مسئول Task.
        $this->actingAs($this->manager)->post("/tickets/{$ticket->id}/convert", [
            'title' => 'سناریو پیامکی Task',
            'assignee_id' => $this->member->id,
        ])->assertRedirect();

        $task = Task::query()->where('source_ticket_id', $ticket->id)->firstOrFail();
        $this->assertSmsPatternQueued('task_assigned', $this->member->phone);

        // تکمیل Task => Resolve و اعلان مشتری.
        $this->actingAs($this->member)->patch("/tasks/{$task->id}/status", [
            'status' => TaskStatus::Completed->value,
        ])->assertRedirect();
        $this->assertSmsPatternQueued('ticket_resolved', $customer->primaryPhone()->value('phone'));

        // ثبت فیش => اعلان داخلی.
        $bank = FinancialBankAccount::query()->create([
            'bank_name' => 'ملت',
            'account_holder' => 'تیم جهش',
            'card_number' => '6104337000000000',
            'is_active' => true,
        ]);

        $this->actingAs($customer, 'customer')->post('/portal/finance/receipts', [
            'bank_account_id' => $bank->id,
            'amount' => '۱,۰۰۰,۰۰۰',
            'paid_at' => today()->format('Y-m-d'),
            'tracking_code' => 'SMS-APPROVE',
            'receipt' => UploadedFile::fake()->create('sms-approve.pdf', 100, 'application/pdf'),
        ])->assertRedirect('/portal/finance');

        $receipt = CustomerPaymentReceipt::query()->where('tracking_code', 'SMS-APPROVE')->firstOrFail();
        $this->assertSmsPatternQueued('receipt_submitted', $this->manager->phone);

        $this->actingAs($this->manager)->patch("/finance/receipts/{$receipt->id}/approve")
            ->assertRedirect();
        $this->assertSmsPatternQueued('receipt_approved', $customer->primaryPhone()->value('phone'));

        // فیش ردشده نیز Pattern مستقل دارد.
        $this->actingAs($customer, 'customer')->post('/portal/finance/receipts', [
            'bank_account_id' => $bank->id,
            'amount' => '۵۰۰,۰۰۰',
            'paid_at' => today()->format('Y-m-d'),
            'tracking_code' => 'SMS-REJECT',
            'receipt' => UploadedFile::fake()->create('sms-reject.pdf', 100, 'application/pdf'),
        ])->assertRedirect('/portal/finance');

        $rejected = CustomerPaymentReceipt::query()->where('tracking_code', 'SMS-REJECT')->firstOrFail();

        $this->actingAs($this->manager)->patch("/finance/receipts/{$rejected->id}/reject", [
            'rejection_reason' => 'تست سناریوی پیامک رد فیش',
        ])->assertRedirect();
        $this->assertSmsPatternQueued('receipt_rejected', $customer->primaryPhone()->value('phone'));

        // در تست Release هیچ پیام واقعی نباید به API ملی پیامک ارسال شود.
        $this->assertDatabaseMissing('sms_logs', ['status' => SmsLog::STATUS_SENT]);
    }

    private function assertSmsPatternQueued(string $patternKey, string $recipient): void
    {
        $this->assertDatabaseHas('sms_logs', [
            'pattern_key' => $patternKey,
            'recipient' => $recipient,
            'status' => SmsLog::STATUS_QUEUED,
        ]);
    }
}
