<?php

namespace Tests\Feature;

use App\Enums\TicketMessageType;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Customer;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffTicketManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;

    protected User $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->manager = User::factory()->create();
        $this->manager->assignRole('project-manager');
        $this->member = User::factory()->create();
        $this->member->assignRole('team-member');
    }

    public function test_guest_and_user_without_permission_cannot_access_staff_tickets(): void
    {
        $this->get('/tickets')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/tickets')->assertForbidden();
    }

    public function test_default_roles_receive_expected_ticket_permissions(): void
    {
        $this->assertTrue($this->manager->hasAllPermissions([
            'tickets.view', 'tickets.view_all', 'tickets.reply', 'tickets.assign',
            'tickets.update_status', 'tickets.internal_notes', 'tickets.convert_to_task', 'tickets.delete',
        ]));
        $this->assertTrue($this->member->hasAllPermissions([
            'tickets.view', 'tickets.reply', 'tickets.update_status', 'tickets.internal_notes',
        ]));
        $this->assertFalse($this->member->can('tickets.view_all'));
        $this->assertFalse($this->member->can('tickets.assign'));
    }

    public function test_member_only_sees_assigned_tickets_while_manager_sees_all(): void
    {
        $assigned = $this->ticket(['subject' => 'تیکت عضو', 'assigned_to' => $this->member->id]);
        $other = $this->ticket(['subject' => 'تیکت دیگر', 'assigned_to' => $this->manager->id]);

        $this->actingAs($this->member)->get('/tickets')
            ->assertOk()->assertSee($assigned->subject)->assertDontSee($other->subject);
        $this->actingAs($this->member)->get("/tickets/{$other->id}")->assertForbidden();
        $this->actingAs($this->manager)->get('/tickets')
            ->assertOk()->assertSee($assigned->subject)->assertSee($other->subject);
    }

    public function test_authorized_staff_can_reply_publicly(): void
    {
        $ticket = $this->ticket(['assigned_to' => $this->member->id]);

        $this->actingAs($this->member)->post("/tickets/{$ticket->id}/reply", ['body' => 'پاسخ عمومی تیم'])
            ->assertRedirect();

        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticket->id,
            'author_type' => User::class,
            'author_id' => $this->member->id,
            'message_type' => 'public',
            'body' => 'پاسخ عمومی تیم',
        ]);
    }

    public function test_authorized_staff_can_create_internal_note_and_unauthorized_user_cannot(): void
    {
        $ticket = $this->ticket(['assigned_to' => $this->member->id]);
        $this->actingAs($this->member)->post("/tickets/{$ticket->id}/internal-note", ['body' => 'یادداشت داخلی تیم'])
            ->assertRedirect();
        $this->assertDatabaseHas('ticket_messages', ['ticket_id' => $ticket->id, 'message_type' => 'internal', 'body' => 'یادداشت داخلی تیم']);

        $restricted = User::factory()->create();
        $restricted->givePermissionTo('tickets.view');
        $ticket->update(['assigned_to' => $restricted->id]);
        $this->actingAs($restricted)->post("/tickets/{$ticket->id}/internal-note", ['body' => 'نباید ثبت شود'])
            ->assertForbidden();
        $this->assertDatabaseMissing('ticket_messages', ['body' => 'نباید ثبت شود']);
    }

    public function test_authorized_user_can_assign_ticket_only_to_active_user(): void
    {
        $ticket = $this->ticket();
        $this->actingAs($this->manager)->patch("/tickets/{$ticket->id}/assignment", ['assignee_id' => $this->member->id])
            ->assertRedirect();
        $this->assertSame($this->member->id, $ticket->fresh()->assigned_to);

        $inactive = User::factory()->inactive()->create();
        $this->actingAs($this->manager)->patch("/tickets/{$ticket->id}/assignment", ['assignee_id' => $inactive->id])
            ->assertSessionHasErrors('assignee_id');
        $this->assertSame($this->member->id, $ticket->fresh()->assigned_to);
    }

    public function test_closing_ticket_sets_closed_at_and_ticket_cannot_reopen_or_receive_public_reply(): void
    {
        $ticket = $this->ticket();
        $this->actingAs($this->manager)->patch("/tickets/{$ticket->id}/status", ['status' => TicketStatus::Closed->value])
            ->assertRedirect();
        $ticket->refresh();
        $this->assertSame(TicketStatus::Closed, $ticket->status);
        $this->assertNotNull($ticket->closed_at);

        $this->actingAs($this->manager)->patch("/tickets/{$ticket->id}/status", ['status' => TicketStatus::InProgress->value])
            ->assertForbidden();
        $this->actingAs($this->manager)->post("/tickets/{$ticket->id}/reply", ['body' => 'پاسخ پس از بسته‌شدن'])
            ->assertForbidden();
        $this->actingAs($this->manager)->post("/tickets/{$ticket->id}/internal-note", ['body' => 'یادداشت پس از بسته‌شدن'])
            ->assertForbidden();
        $this->assertSame(TicketStatus::Closed, $ticket->fresh()->status);
    }

    public function test_staff_search_and_filters_work(): void
    {
        $customer = Customer::factory()->create(['name' => 'مشتری آفتاب', 'company_name' => 'شرکت سپهر']);
        $customer->phones()->create(['phone' => '09121111111', 'is_primary' => true]);
        $target = $this->ticket([
            'customer_id' => $customer->id,
            'subject' => 'مشکل هاست ویژه',
            'priority' => TicketPriority::Urgent,
            'status' => TicketStatus::InReview,
            'assigned_to' => $this->member->id,
        ]);
        $other = $this->ticket(['subject' => 'تیکت نامرتبط']);

        foreach (['هاست', 'آفتاب', 'سپهر', '۰۹۱۲۱۱۱'] as $query) {
            $this->actingAs($this->manager)->get('/tickets?q='.urlencode($query))
                ->assertOk()->assertSee($target->subject)->assertDontSee($other->subject);
        }
        foreach (['status=in_review', 'priority=urgent', "assignee_id={$this->member->id}", "customer_id={$customer->id}"] as $query) {
            $this->actingAs($this->manager)->get("/tickets?{$query}")
                ->assertOk()->assertSee($target->subject)->assertDontSee($other->subject);
        }
    }

    public function test_staff_customer_profile_shows_recent_visible_tickets(): void
    {
        $customer = Customer::factory()->create();
        $ticket = $this->ticket(['customer_id' => $customer->id, 'subject' => 'تیکت پروفایل']);

        $this->actingAs($this->manager)->get("/customers/{$customer->id}")
            ->assertOk()->assertSee('تیکت‌های اخیر')->assertSee($ticket->subject);
    }

    public function test_dashboard_ticket_metrics_are_scoped_to_user_visibility(): void
    {
        $this->ticket(['subject' => 'تیکت عضو', 'assigned_to' => $this->member->id, 'status' => TicketStatus::New]);
        $this->ticket(['subject' => 'تیکت مدیر', 'assigned_to' => $this->manager->id, 'status' => TicketStatus::WaitingCustomer]);

        $this->actingAs($this->member)->get('/dashboard')
            ->assertOk()->assertSee('تیکت عضو')->assertDontSee('تیکت مدیر');
        $this->actingAs($this->manager)->get('/dashboard')
            ->assertOk()->assertSee('تیکت عضو')->assertSee('تیکت مدیر');
    }

    public function test_authorized_staff_soft_deletes_ticket(): void
    {
        $ticket = $this->ticket();

        $this->actingAs($this->manager)->delete("/tickets/{$ticket->id}")->assertRedirect('/tickets');
        $this->assertSoftDeleted($ticket);
    }

    private function ticket(array $attributes = []): Ticket
    {
        $ticket = Ticket::factory()->create($attributes);
        TicketMessage::factory()->for($ticket)->create([
            'author_type' => Customer::class,
            'author_id' => $ticket->customer_id,
            'message_type' => TicketMessageType::Public,
        ]);

        return $ticket;
    }
}
