<?php

namespace Tests\Feature;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\TicketMessageType;
use App\Enums\TicketPriority;
use App\Models\Customer;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketConversionTest extends TestCase
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

    public function test_authorized_user_converts_ticket_to_linked_task_with_mapped_data(): void
    {
        $ticket = $this->ticket(TicketPriority::Urgent);

        $this->actingAs($this->manager)->post("/tickets/{$ticket->id}/convert", [
            'title' => 'تسک پیگیری ویژه',
            'assignee_id' => $this->member->id,
            'start_date' => '2026-08-16',
            'due_date' => '2026-08-20',
        ])->assertRedirect();

        $task = Task::query()->firstOrFail();
        $this->assertSame($ticket->customer_id, $task->customer_id);
        $this->assertSame($ticket->id, $task->source_ticket_id);
        $this->assertSame($this->manager->id, $task->created_by);
        $this->assertSame($this->member->id, $task->assignee_id);
        $this->assertSame(TaskPriority::Urgent, $task->priority);
        $this->assertSame(TaskStatus::New, $task->status);
        $this->assertStringContainsString('شرح اولیه مشتری', $task->description);
        $this->assertSame($task->id, $ticket->fresh()->task->id);
    }

    public function test_same_ticket_cannot_be_converted_twice(): void
    {
        $ticket = $this->ticket();
        $payload = ['title' => 'تسک یکتا', 'assignee_id' => $this->member->id];

        $this->actingAs($this->manager)->post("/tickets/{$ticket->id}/convert", $payload)->assertRedirect();
        $this->actingAs($this->manager)->post("/tickets/{$ticket->id}/convert", $payload)
            ->assertSessionHasErrors('ticket');
        $this->assertDatabaseCount('tasks', 1);
    }

    public function test_unauthorized_staff_cannot_convert_ticket(): void
    {
        $ticket = $this->ticket();
        $ticket->update(['assigned_to' => $this->member->id]);

        $this->actingAs($this->member)->post("/tickets/{$ticket->id}/convert", [
            'title' => 'تسک غیرمجاز', 'assignee_id' => $this->member->id,
        ])->assertForbidden();
        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_conversion_rejects_inactive_assignee_and_invalid_date_order(): void
    {
        $ticket = $this->ticket();
        $inactive = User::factory()->inactive()->create();

        $this->actingAs($this->manager)->post("/tickets/{$ticket->id}/convert", [
            'title' => 'تسک نامعتبر',
            'assignee_id' => $inactive->id,
            'start_date' => '2026-08-20',
            'due_date' => '2026-08-19',
        ])->assertSessionHasErrors(['assignee_id', 'due_date']);
        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_customer_never_sees_converted_task_information(): void
    {
        $ticket = $this->ticket();
        $task = Task::factory()->create([
            'customer_id' => $ticket->customer_id,
            'source_ticket_id' => $ticket->id,
            'assignee_id' => $this->member->id,
            'created_by' => $this->manager->id,
            'title' => 'تسک داخلی محرمانه',
        ]);

        $this->actingAs($ticket->customer, 'customer')->get("/portal/tickets/{$ticket->id}")
            ->assertOk()->assertDontSee('تسک داخلی محرمانه')->assertDontSee("/tasks/{$task->id}");
    }

    public function test_soft_deleting_task_preserves_ticket_and_staff_relation_history(): void
    {
        $ticket = $this->ticket();
        $task = Task::factory()->create([
            'customer_id' => $ticket->customer_id,
            'source_ticket_id' => $ticket->id,
            'assignee_id' => $this->member->id,
            'created_by' => $this->manager->id,
        ]);
        $task->delete();

        $this->assertDatabaseHas('tickets', ['id' => $ticket->id]);
        $this->assertTrue($ticket->fresh()->task->trashed());
    }

    public function test_staff_task_page_links_back_to_source_ticket(): void
    {
        $ticket = $this->ticket();
        $task = Task::factory()->create([
            'customer_id' => $ticket->customer_id,
            'source_ticket_id' => $ticket->id,
            'assignee_id' => $this->manager->id,
            'created_by' => $this->manager->id,
        ]);

        $this->actingAs($this->manager)->get("/tasks/{$task->id}")
            ->assertOk()->assertSee("تیکت #{$ticket->id}");
    }

    private function ticket(TicketPriority $priority = TicketPriority::Normal): Ticket
    {
        $ticket = Ticket::factory()->create(['priority' => $priority]);
        TicketMessage::factory()->for($ticket)->create([
            'author_type' => Customer::class,
            'author_id' => $ticket->customer_id,
            'message_type' => TicketMessageType::Public,
            'body' => 'شرح اولیه مشتری',
        ]);

        return $ticket;
    }
}
