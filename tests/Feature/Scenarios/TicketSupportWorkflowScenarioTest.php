<?php

namespace Tests\Feature\Scenarios;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use PHPUnit\Framework\Attributes\Group;

#[Group('release')]
class TicketSupportWorkflowScenarioTest extends ScenarioTestCase
{
    public function test_customer_and_team_can_complete_support_conversation_and_closed_ticket_is_final(): void
    {
        $customer = $this->customerWithPhone('09350000021');
        $this->actingAs($customer, 'customer');

        $this->post('/portal/tickets', [
            'subject' => 'سناریو مشکل ورود مشتری',
            'priority' => 'urgent',
            'description' => 'مشتری امکان ورود به سایت را ندارد.',
        ])->assertRedirect();

        $ticket = Ticket::query()->where('subject', 'سناریو مشکل ورود مشتری')->firstOrFail();

        $this->assertSame(TicketStatus::New, $ticket->status);
        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticket->id,
            'body' => 'مشتری امکان ورود به سایت را ندارد.',
            'message_type' => 'public',
        ]);

        // مدیر تیکت را به عضو تیم ارجاع می‌دهد؛ New => InReview.
        $this->actingAs($this->manager)->patch("/tickets/{$ticket->id}/assignment", [
            'assignee_id' => $this->member->id,
        ])->assertRedirect();

        $ticket->refresh();
        $this->assertSame($this->member->id, $ticket->assigned_to);
        $this->assertSame(TicketStatus::InReview, $ticket->status);

        // یادداشت داخلی فقط برای تیم است.
        $this->actingAs($this->member)->post("/tickets/{$ticket->id}/internal-note", [
            'body' => 'اطلاعات داخلی محرمانه برای تیم',
        ])->assertRedirect();

        // پاسخ عمومی و انتظار برای مشتری.
        $this->actingAs($this->member)->post("/tickets/{$ticket->id}/reply", [
            'body' => 'لطفاً یک بار مجدد ورود را تست کنید.',
            'after_reply_status' => TicketStatus::WaitingCustomer->value,
        ])->assertRedirect();

        $this->assertSame(TicketStatus::WaitingCustomer, $ticket->fresh()->status);

        // مشتری پاسخ عمومی را می‌بیند ولی یادداشت داخلی را نه.
        $this->actingAs($customer, 'customer')->get("/portal/tickets/{$ticket->id}")
            ->assertOk()
            ->assertSee('لطفاً یک بار مجدد ورود را تست کنید.')
            ->assertDontSee('اطلاعات داخلی محرمانه برای تیم');

        // پاسخ مشتری تیکت را دوباره وارد صف بررسی می‌کند.
        $this->actingAs($customer, 'customer')->post("/portal/tickets/{$ticket->id}/replies", [
            'body' => 'تست کردم، هنوز مشکل پابرجاست.',
        ])->assertRedirect();

        $this->assertSame(TicketStatus::InReview, $ticket->fresh()->status);

        // بستن تیکت نهایی است.
        $this->actingAs($this->manager)->patch("/tickets/{$ticket->id}/status", [
            'status' => TicketStatus::Closed->value,
        ])->assertRedirect();

        $ticket->refresh();
        $this->assertSame(TicketStatus::Closed, $ticket->status);
        $this->assertNotNull($ticket->closed_at);

        $this->actingAs($customer, 'customer')->post("/portal/tickets/{$ticket->id}/replies", [
            'body' => 'نباید بعد از بسته شدن ثبت شود.',
        ])->assertSessionHasErrors('ticket');

        $this->assertDatabaseMissing('ticket_messages', [
            'ticket_id' => $ticket->id,
            'body' => 'نباید بعد از بسته شدن ثبت شود.',
        ]);
    }

    public function test_dashboard_only_shows_active_tickets_while_ticket_archive_shows_everything(): void
    {
        $customer = $this->customerWithPhone('09350000022');

        Ticket::factory()->for($customer)->create([
            'subject' => 'سناریو تیکت فعال داشبورد',
            'status' => TicketStatus::InProgress,
        ]);

        Ticket::factory()->for($customer)->create([
            'subject' => 'سناریو تیکت حل شده آرشیو',
            'status' => TicketStatus::Resolved,
        ]);

        Ticket::factory()->for($customer)->create([
            'subject' => 'سناریو تیکت بسته شده آرشیو',
            'status' => TicketStatus::Closed,
            'closed_at' => now(),
        ]);

        $this->actingAs($customer, 'customer')->get('/portal')
            ->assertOk()
            ->assertSee('سناریو تیکت فعال داشبورد')
            ->assertDontSee('سناریو تیکت حل شده آرشیو')
            ->assertDontSee('سناریو تیکت بسته شده آرشیو');

        $this->actingAs($customer, 'customer')->get('/portal/tickets')
            ->assertOk()
            ->assertSee('سناریو تیکت فعال داشبورد')
            ->assertSee('سناریو تیکت حل شده آرشیو')
            ->assertSee('سناریو تیکت بسته شده آرشیو');
    }
}
