<?php

namespace Tests\Feature\Scenarios;

use App\Models\Customer;
use App\Models\Task;
use App\Models\Ticket;
use PHPUnit\Framework\Attributes\Group;

#[Group('release')]
class PermissionMatrixScenarioTest extends ScenarioTestCase
{
    public function test_default_roles_match_the_release_access_matrix(): void
    {
        $customer = Customer::factory()->create();
        $ticket = Ticket::factory()->for($customer)->create([
            'assigned_to' => $this->member->id,
        ]);
        $task = Task::factory()->create([
            'assignee_id' => $this->member->id,
            'created_by' => $this->manager->id,
        ]);

        // Project Manager: عملیات مشتری، تسک، تیکت و مالی؛ بدون مدیریت سیستم.
        $this->actingAs($this->manager)->get('/dashboard')->assertOk();
        $this->actingAs($this->manager)->get('/customers')->assertOk();
        $this->actingAs($this->manager)->get('/tasks?scope=all')->assertOk();
        $this->actingAs($this->manager)->get('/tickets')->assertOk();
        $this->actingAs($this->manager)->get('/finance')->assertOk();
        $this->actingAs($this->manager)->get('/team')->assertForbidden();
        $this->actingAs($this->manager)->get('/roles')->assertForbidden();
        $this->actingAs($this->manager)->get('/finance/bank-accounts')->assertForbidden();
        $this->actingAs($this->manager)->get('/settings/sms')->assertForbidden();

        // Team Member: فقط عملیات داخلی خودش؛ بدون مشتری و مالی و تنظیمات.
        $this->actingAs($this->member)->get('/dashboard')->assertOk();
        $this->actingAs($this->member)->get('/tasks')->assertOk();
        $this->actingAs($this->member)->get("/tasks/{$task->id}")->assertOk();
        $this->actingAs($this->member)->get('/tickets')->assertOk();
        $this->actingAs($this->member)->get("/tickets/{$ticket->id}")->assertOk();
        $this->actingAs($this->member)->get('/customers')->assertForbidden();
        $this->actingAs($this->member)->get('/finance')->assertForbidden();
        $this->actingAs($this->member)->get('/team')->assertForbidden();
        $this->actingAs($this->member)->get('/roles')->assertForbidden();
        $this->actingAs($this->member)->get('/settings/sms')->assertForbidden();

        // عضو تیم حتی با درخواست Crafted هم نباید بتواند تیکت را Assign کند.
        $this->actingAs($this->member)->patch("/tickets/{$ticket->id}/assignment", [
            'assignee_id' => $this->manager->id,
        ])->assertForbidden();
        $this->assertSame($this->member->id, $ticket->fresh()->assigned_to);

        // Super Admin باید به صفحات مدیریتی حساس دسترسی داشته باشد.
        $this->actingAs($this->admin)->get('/team')->assertOk();
        $this->actingAs($this->admin)->get('/roles')->assertOk();
        $this->actingAs($this->admin)->get('/finance/bank-accounts')->assertOk();
        $this->actingAs($this->admin)->get('/settings/sms')->assertOk();
    }
}
