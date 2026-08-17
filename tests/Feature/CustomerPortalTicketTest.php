<?php

namespace Tests\Feature;

use App\Enums\TicketMessageType;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Customer;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerPortalTicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_create_ticket_with_initial_message_transactionally(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($customer, 'customer')->post('/portal/tickets', [
            'subject' => 'مشکل ورود به سایت',
            'priority' => TicketPriority::Urgent->value,
            'description' => 'امکان ورود به سایت وجود ندارد.',
        ])->assertRedirect();

        $ticket = Ticket::query()->firstOrFail();
        $this->assertSame($customer->id, $ticket->customer_id);
        $this->assertSame(TicketPriority::Urgent, $ticket->priority);
        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticket->id,
            'author_type' => Customer::class,
            'author_id' => $customer->id,
            'message_type' => 'public',
            'body' => 'امکان ورود به سایت وجود ندارد.',
        ]);
    }

    public function test_customer_sees_only_own_tickets_and_cannot_open_another_ticket(): void
    {
        $customer = Customer::factory()->create();
        $other = Customer::factory()->create();
        $ownTicket = $this->ticketFor($customer, 'درخواست خودم');
        $otherTicket = $this->ticketFor($other, 'درخواست مشتری دیگر');

        $this->actingAs($customer, 'customer')->get('/portal/tickets')
            ->assertOk()->assertSee($ownTicket->subject)->assertDontSee($otherTicket->subject);
        $this->actingAs($customer, 'customer')->get("/portal/tickets/{$otherTicket->id}")->assertNotFound();
    }

    public function test_customer_can_reply_to_open_ticket(): void
    {
        $customer = Customer::factory()->create();
        $ticket = $this->ticketFor($customer);

        $this->actingAs($customer, 'customer')->post("/portal/tickets/{$ticket->id}/replies", [
            'body' => 'پیام تکمیلی مشتری',
        ])->assertRedirect();

        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticket->id,
            'author_type' => Customer::class,
            'message_type' => 'public',
            'body' => 'پیام تکمیلی مشتری',
        ]);
    }

    public function test_customer_cannot_reply_to_closed_ticket(): void
    {
        $customer = Customer::factory()->create();
        $ticket = $this->ticketFor($customer, status: TicketStatus::Closed);

        $this->actingAs($customer, 'customer')->post("/portal/tickets/{$ticket->id}/replies", [
            'body' => 'تلاش برای بازگشایی',
        ])->assertSessionHasErrors('ticket');

        $this->assertDatabaseMissing('ticket_messages', ['ticket_id' => $ticket->id, 'body' => 'تلاش برای بازگشایی']);
        $this->assertSame(TicketStatus::Closed, $ticket->fresh()->status);
    }

    public function test_customer_reply_moves_waiting_or_resolved_ticket_to_in_review(): void
    {
        foreach ([TicketStatus::WaitingCustomer, TicketStatus::Resolved] as $status) {
            $customer = Customer::factory()->create();
            $ticket = $this->ticketFor($customer, "تیکت {$status->value}", $status);

            $this->actingAs($customer, 'customer')->post("/portal/tickets/{$ticket->id}/replies", [
                'body' => 'پاسخ مشتری',
            ])->assertRedirect();

            $this->assertSame(TicketStatus::InReview, $ticket->fresh()->status);
        }
    }

    public function test_customer_never_sees_internal_notes(): void
    {
        $customer = Customer::factory()->create();
        $staff = User::factory()->create();
        $ticket = $this->ticketFor($customer);
        TicketMessage::factory()->for($ticket)->create([
            'author_type' => User::class,
            'author_id' => $staff->id,
            'message_type' => TicketMessageType::Internal,
            'body' => 'یادداشت فوق محرمانه داخلی',
        ]);
        TicketMessage::factory()->for($ticket)->create([
            'author_type' => User::class,
            'author_id' => $staff->id,
            'message_type' => TicketMessageType::Public,
            'body' => 'پاسخ عمومی پشتیبانی',
        ]);

        $this->actingAs($customer, 'customer')->get("/portal/tickets/{$ticket->id}")
            ->assertOk()->assertSee('پاسخ عمومی پشتیبانی')->assertDontSee('یادداشت فوق محرمانه داخلی');
    }

    public function test_closed_ticket_page_is_read_only_and_offers_new_request(): void
    {
        $customer = Customer::factory()->create();
        $ticket = $this->ticketFor($customer, status: TicketStatus::Closed);

        $this->actingAs($customer, 'customer')->get("/portal/tickets/{$ticket->id}")
            ->assertOk()->assertSee('این درخواست بسته شده است')->assertSee('ثبت درخواست جدید')
            ->assertDontSee('پیام خود را بنویسید');
    }

    public function test_customer_profile_is_read_only_and_does_not_expose_internal_notes(): void
    {
        $customer = Customer::factory()->create(['notes' => 'یادداشت داخلی مشتری']);
        $customer->phones()->create(['phone' => '09121111111', 'is_primary' => true]);

        $this->actingAs($customer, 'customer')->get('/portal/profile')
            ->assertOk()->assertSee($customer->name)->assertSee('09121111111')
            ->assertDontSee('یادداشت داخلی مشتری');
    }

    private function ticketFor(Customer $customer, string $subject = 'درخواست آزمایشی', TicketStatus $status = TicketStatus::New): Ticket
    {
        $ticket = Ticket::factory()->for($customer)->create([
            'subject' => $subject,
            'status' => $status,
            'closed_at' => $status === TicketStatus::Closed ? now() : null,
        ]);
        TicketMessage::factory()->for($ticket)->create([
            'author_type' => Customer::class,
            'author_id' => $customer->id,
            'message_type' => TicketMessageType::Public,
        ]);

        return $ticket;
    }
}
