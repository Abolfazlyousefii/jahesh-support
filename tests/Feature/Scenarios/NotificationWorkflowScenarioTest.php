<?php

namespace Tests\Feature\Scenarios;

use App\Enums\TicketStatus;
use App\Models\CustomerPaymentReceipt;
use App\Models\FinancialBankAccount;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Group;

#[Group('release')]
class NotificationWorkflowScenarioTest extends ScenarioTestCase
{
    public function test_staff_notifications_are_personal_and_cannot_be_opened_by_another_user(): void
    {
        $customer = $this->customerWithPhone('09350000061');
        $otherMember = User::factory()->create([
            'name' => 'عضو دوم',
            'phone' => '09120000061',
        ]);
        $otherMember->assignRole('team-member');

        $this->actingAs($customer, 'customer')->post('/portal/tickets', [
            'subject' => 'تیکت برای تست اعلان شخصی',
            'priority' => 'important',
            'description' => 'اعلان این تیکت باید فقط به افراد مرتبط برسد.',
        ])->assertRedirect();

        $ticket = Ticket::query()->where('subject', 'تیکت برای تست اعلان شخصی')->firstOrFail();

        // تیکت جدید فقط برای مدیرانی که همه تیکت‌ها را می‌بینند اعلان داخلی می‌سازد.
        $this->assertTrue($this->hasEvent($this->manager, 'ticket.created'));
        $this->assertTrue($this->hasEvent($this->admin, 'ticket.created'));
        $this->assertFalse($this->hasEvent($this->member, 'ticket.created'));
        $this->assertFalse($this->hasEvent($otherMember, 'ticket.created'));

        // بعد از ارجاع، فقط مسئول جدید اعلان شخصی می‌گیرد.
        $this->actingAs($this->manager)->patch("/tickets/{$ticket->id}/assignment", [
            'assignee_id' => $this->member->id,
        ])->assertRedirect();

        $assignedNotification = $this->eventNotification($this->member, 'ticket.assigned');
        $this->assertNotNull($assignedNotification);
        $this->assertFalse($this->hasEvent($otherMember, 'ticket.assigned'));

        // مدیر نمی‌تواند اعلان متعلق به عضو تیم را حتی با UUID مستقیم باز کند.
        $this->actingAs($this->manager)
            ->post("/notifications/{$assignedNotification->id}/open")
            ->assertNotFound();

        // صاحب اعلان می‌تواند آن را باز کند و فقط همان اعلان خوانده می‌شود.
        $this->actingAs($this->member)
            ->post("/notifications/{$assignedNotification->id}/open")
            ->assertRedirect("/tickets/{$ticket->id}");

        $this->assertNotNull($assignedNotification->fresh()->read_at);
    }

    public function test_customer_notifications_are_private_and_follow_ticket_and_finance_events(): void
    {
        Storage::fake('local');

        $customer = $this->customerWithPhone('09350000062');
        $otherCustomer = $this->customerWithPhone('09350000063');

        $this->actingAs($customer, 'customer')->post('/portal/tickets', [
            'subject' => 'تیکت اعلان مشتری',
            'priority' => 'normal',
            'description' => 'تست اعلان‌های پنل مشتری.',
        ])->assertRedirect();

        $ticket = Ticket::query()->where('subject', 'تیکت اعلان مشتری')->firstOrFail();
        $this->assertFalse($this->hasEvent($customer, 'ticket.created'));

        $this->actingAs($this->manager)->patch("/tickets/{$ticket->id}/assignment", [
            'assignee_id' => $this->member->id,
        ])->assertRedirect();

        $this->actingAs($this->member)->post("/tickets/{$ticket->id}/reply", [
            'body' => 'پاسخ جدید پشتیبانی برای مشتری.',
            'after_reply_status' => TicketStatus::WaitingCustomer->value,
        ])->assertRedirect();

        $staffReply = $this->eventNotification($customer, 'ticket.staff_reply');
        $this->assertNotNull($staffReply);

        // حتی عضو تیم/مدیر هم اعلان شخصی مشتری را از مسیر پنل مدیریت نمی‌تواند باز کند.
        $this->actingAs($this->manager)
            ->post("/notifications/{$staffReply->id}/open")
            ->assertNotFound();

        // مشتری دیگر هیچ دسترسی‌ای به اعلان مشتری اول ندارد.
        $this->actingAs($otherCustomer, 'customer')
            ->post("/portal/notifications/{$staffReply->id}/open")
            ->assertNotFound();

        $bank = FinancialBankAccount::query()->create([
            'bank_name' => 'ملت',
            'account_holder' => 'تیم جهش',
            'card_number' => '6104337000000000',
            'is_active' => true,
        ]);

        $this->actingAs($customer, 'customer')->post('/portal/finance/receipts', [
            'bank_account_id' => $bank->id,
            'amount' => '۱,۲۰۰,۰۰۰',
            'paid_at' => today()->format('Y-m-d'),
            'tracking_code' => 'NOTIFY-APPROVE',
            'receipt' => UploadedFile::fake()->create('notify.pdf', 100, 'application/pdf'),
        ])->assertRedirect('/portal/finance');

        $receipt = CustomerPaymentReceipt::query()->where('tracking_code', 'NOTIFY-APPROVE')->firstOrFail();

        $this->assertFalse($this->hasEvent($customer, 'finance.receipt_submitted'));
        $this->assertTrue($this->hasEvent($this->manager, 'finance.receipt_submitted'));
        $this->assertFalse($this->hasEvent($this->member, 'finance.receipt_submitted'));

        $this->actingAs($this->manager)
            ->patch("/finance/receipts/{$receipt->id}/approve")
            ->assertRedirect();

        $this->assertTrue($this->hasEvent($customer, 'finance.receipt_approved'));
        $this->assertFalse($this->hasEvent($otherCustomer, 'finance.receipt_approved'));
    }

    public function test_mark_all_read_only_changes_current_users_notifications(): void
    {
        $customer = $this->customerWithPhone('09350000064');

        $this->actingAs($customer, 'customer')->post('/portal/tickets', [
            'subject' => 'تیکت خواندن اعلان‌ها',
            'priority' => 'normal',
            'description' => 'برای تست خواندن اعلان‌ها.',
        ])->assertRedirect();

        $this->assertGreaterThan(0, $this->manager->unreadNotifications()->count());
        $this->assertGreaterThan(0, $this->admin->unreadNotifications()->count());

        $this->actingAs($this->manager)
            ->post('/notifications/read-all')
            ->assertRedirect();

        $this->assertSame(0, $this->manager->unreadNotifications()->count());
        $this->assertGreaterThan(0, $this->admin->unreadNotifications()->count());
    }

    private function hasEvent(object $notifiable, string $event): bool
    {
        return $this->eventNotification($notifiable, $event) !== null;
    }

    private function eventNotification(object $notifiable, string $event): mixed
    {
        return $notifiable->notifications()->get()
            ->first(fn ($notification) => ($notification->data['event'] ?? null) === $event);
    }
}
