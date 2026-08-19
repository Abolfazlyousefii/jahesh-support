<?php

namespace Tests\Feature\Scenarios;

use App\Enums\TaskStatus;
use App\Enums\TicketMessageType;
use App\Enums\TicketStatus;
use App\Models\Customer;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\TicketMessage;
use PHPUnit\Framework\Attributes\Group;

#[Group('release')]
class TicketTaskWorkflowScenarioTest extends ScenarioTestCase
{
    public function test_ticket_to_task_statuses_stay_synchronized_until_ticket_is_closed(): void
    {
        $customer = $this->customerWithPhone('09350000031');
        $ticket = Ticket::factory()->for($customer)->create([
            'subject' => 'سناریو تبدیل تیکت به تسک',
            'status' => TicketStatus::InReview,
        ]);

        TicketMessage::factory()->for($ticket)->create([
            'author_type' => Customer::class,
            'author_id' => $customer->id,
            'message_type' => TicketMessageType::Public,
            'body' => 'شرح اولیه سناریو تبدیل تیکت',
        ]);

        $this->actingAs($this->manager)->post("/tickets/{$ticket->id}/convert", [
            'title' => 'سناریو تسک حاصل از تیکت',
            'assignee_id' => $this->member->id,
            'start_date' => today()->format('Y-m-d'),
            'due_date' => today()->addDays(2)->format('Y-m-d'),
        ])->assertRedirect();

        $task = Task::query()->where('source_ticket_id', $ticket->id)->firstOrFail();

        $this->assertSame($customer->id, $task->customer_id);
        $this->assertSame($this->member->id, $task->assignee_id);
        $this->assertSame(TaskStatus::New, $task->status);
        $this->assertSame(TicketStatus::InProgress, $ticket->fresh()->status);

        // تکمیل تسک => Resolve شدن تیکت.
        $this->actingAs($this->member)->patch("/tasks/{$task->id}/status", [
            'status' => TaskStatus::Completed->value,
        ])->assertRedirect();

        $task->refresh();
        $this->assertSame(TaskStatus::Completed, $task->status);
        $this->assertNotNull($task->completed_at);
        $this->assertSame(TicketStatus::Resolved, $ticket->fresh()->status);

        // بازگشت تسک به جریان کار => تیکت دوباره InProgress می‌شود.
        $this->actingAs($this->member)->patch("/tasks/{$task->id}/status", [
            'status' => TaskStatus::InProgress->value,
        ])->assertRedirect();

        $task->refresh();
        $this->assertSame(TaskStatus::InProgress, $task->status);
        $this->assertNull($task->completed_at);
        $this->assertSame(TicketStatus::InProgress, $ticket->fresh()->status);

        // تکمیل مجدد و سپس بستن نهایی تیکت.
        $this->actingAs($this->member)->patch("/tasks/{$task->id}/status", [
            'status' => TaskStatus::Completed->value,
        ])->assertRedirect();

        $this->assertSame(TicketStatus::Resolved, $ticket->fresh()->status);

        $this->actingAs($this->manager)->patch("/tickets/{$ticket->id}/status", [
            'status' => TicketStatus::Closed->value,
        ])->assertRedirect();

        $this->assertSame(TicketStatus::Closed, $ticket->fresh()->status);

        // بعد از بسته شدن نهایی تیکت، تغییر داخلی Task نباید تیکت را باز کند.
        $this->actingAs($this->member)->patch("/tasks/{$task->id}/status", [
            'status' => TaskStatus::InProgress->value,
        ])->assertRedirect();

        $this->assertSame(TaskStatus::InProgress, $task->fresh()->status);
        $this->assertSame(TicketStatus::Closed, $ticket->fresh()->status);
    }
}
