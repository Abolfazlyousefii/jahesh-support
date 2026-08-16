<?php

namespace Tests\Feature;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Customer;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $member;

    protected User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('super-admin');
        $this->member = User::factory()->create();
        $this->member->assignRole('team-member');
        $this->manager = User::factory()->create();
        $this->manager->assignRole('project-manager');
    }

    public function test_guest_cannot_view_tasks_and_user_without_permission_receives_403(): void
    {
        $this->get('/tasks')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/tasks')->assertForbidden();
    }

    public function test_default_roles_receive_expected_task_permissions(): void
    {
        $this->assertTrue($this->manager->hasAllPermissions([
            'tasks.view', 'tasks.create', 'tasks.update', 'tasks.delete',
            'tasks.assign', 'tasks.view_all', 'tasks.update_status',
        ]));
        $this->assertTrue($this->member->hasAllPermissions([
            'tasks.view', 'tasks.create', 'tasks.update', 'tasks.update_status',
        ]));
        $this->assertFalse($this->member->can('tasks.assign'));
        $this->assertFalse($this->member->can('tasks.view_all'));
    }

    public function test_user_without_view_all_only_sees_and_opens_own_tasks(): void
    {
        $own = $this->createTask('تسک شخصی', $this->member);
        $other = $this->createTask('تسک دیگران', $this->manager);

        $this->actingAs($this->member)->get('/tasks?scope=all')
            ->assertOk()->assertSee($own->title)->assertDontSee($other->title);
        $this->actingAs($this->member)->get("/tasks/{$other->id}")->assertForbidden();
    }

    public function test_user_with_view_all_and_super_admin_can_see_team_tasks(): void
    {
        $task = $this->createTask('تسک تیمی', $this->member);

        $this->actingAs($this->manager)->get('/tasks?scope=all')->assertOk()->assertSee($task->title);
        $this->actingAs($this->admin)->get('/tasks?scope=all')->assertOk()->assertSee($task->title);
    }

    public function test_task_create_edit_and_show_views_render(): void
    {
        $task = $this->createTask('تسک فرم', $this->member);

        $this->actingAs($this->member)->get('/tasks/create')->assertOk()->assertSee('تاریخ شروع');
        $this->actingAs($this->member)->get("/tasks/{$task->id}/edit")->assertOk()->assertSee($task->title);
        $this->actingAs($this->member)->get("/tasks/{$task->id}")->assertOk()->assertSee('تغییر سریع وضعیت');
    }

    public function test_authorized_user_can_create_task_and_creator_is_authenticated_user(): void
    {
        $this->actingAs($this->member)->post('/tasks', $this->payload([
            'created_by' => $this->admin->id,
        ]))
            ->assertRedirect();

        $task = Task::query()->firstOrFail();
        $this->assertSame($this->member->id, $task->created_by);
        $this->assertSame($this->member->id, $task->assignee_id);
        $this->assertSame(TaskPriority::Important, $task->priority);
    }

    public function test_soft_deleting_customer_or_user_does_not_delete_task_history(): void
    {
        $customer = Customer::factory()->create();
        $assignee = User::factory()->create();
        $task = $this->createTask('تاریخچه محفوظ', $assignee, ['customer_id' => $customer->id]);

        $customer->delete();
        $assignee->delete();

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'customer_id' => $customer->id, 'assignee_id' => $assignee->id]);
        $this->assertTrue($task->fresh()->customer->trashed());
        $this->assertTrue($task->fresh()->assignee->trashed());
    }

    public function test_team_member_cannot_assign_task_to_another_user_with_crafted_request(): void
    {
        $this->actingAs($this->member)->post('/tasks', $this->payload([
            'assignee_id' => $this->manager->id,
        ]))->assertRedirect();

        $this->assertDatabaseHas('tasks', [
            'assignee_id' => $this->member->id,
            'created_by' => $this->member->id,
        ]);
    }

    public function test_user_with_assign_permission_can_assign_to_another_active_user(): void
    {
        $this->actingAs($this->manager)->post('/tasks', $this->payload([
            'assignee_id' => $this->member->id,
        ]))->assertRedirect();

        $this->assertDatabaseHas('tasks', [
            'assignee_id' => $this->member->id,
            'created_by' => $this->manager->id,
        ]);
    }

    public function test_task_cannot_be_assigned_to_inactive_or_soft_deleted_user(): void
    {
        $inactive = User::factory()->inactive()->create();
        $deleted = User::factory()->create();
        $deleted->delete();

        $this->actingAs($this->manager)->post('/tasks', $this->payload(['assignee_id' => $inactive->id]))
            ->assertSessionHasErrors('assignee_id');
        $this->actingAs($this->manager)->post('/tasks', $this->payload(['assignee_id' => $deleted->id]))
            ->assertSessionHasErrors('assignee_id');
    }

    public function test_customer_is_optional_and_valid_customer_can_be_linked(): void
    {
        $this->actingAs($this->member)->post('/tasks', $this->payload([
            'customer_id' => '', 'start_date' => '',
        ]))->assertRedirect();
        $this->assertNull(Task::query()->firstOrFail()->customer_id);

        $customer = Customer::factory()->create();
        $this->actingAs($this->member)->post('/tasks', $this->payload([
            'title' => 'تسک مشتری', 'customer_id' => $customer->id,
        ]))->assertRedirect();
        $this->assertDatabaseHas('tasks', ['title' => 'تسک مشتری', 'customer_id' => $customer->id]);
    }

    public function test_task_validation_rejects_missing_title_invalid_enum_and_invalid_date_order(): void
    {
        $this->actingAs($this->member)->post('/tasks', $this->payload([
            'title' => '',
            'priority' => 'critical',
            'status' => 'done',
            'start_date' => '2026-08-20',
            'due_date' => '2026-08-19',
        ]))->assertSessionHasErrors(['title', 'priority', 'status', 'due_date']);
    }

    public function test_authorized_user_can_update_own_task(): void
    {
        $task = $this->createTask('عنوان قدیمی', $this->member);

        $this->actingAs($this->member)->put("/tasks/{$task->id}", $this->payload([
            'title' => 'عنوان جدید',
            'priority' => TaskPriority::Urgent->value,
        ]))->assertRedirect("/tasks/{$task->id}");

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'عنوان جدید', 'priority' => 'urgent']);
    }

    public function test_user_without_assign_permission_cannot_change_assignee_on_update(): void
    {
        $task = $this->createTask('تسک امن', $this->member);

        $this->actingAs($this->member)->put("/tasks/{$task->id}", $this->payload([
            'assignee_id' => $this->manager->id,
        ]))->assertRedirect();

        $this->assertSame($this->member->id, $task->fresh()->assignee_id);
    }

    public function test_user_cannot_update_another_users_task_by_url(): void
    {
        $task = $this->createTask('تسک محافظت‌شده', $this->manager);

        $this->actingAs($this->member)->put("/tasks/{$task->id}", $this->payload())->assertForbidden();
    }

    public function test_quick_status_update_sets_and_clears_completed_at(): void
    {
        $task = $this->createTask('تکمیل تسک', $this->member);

        $this->actingAs($this->member)->patch("/tasks/{$task->id}/status", [
            'status' => TaskStatus::Completed->value,
        ])->assertRedirect();
        $this->assertNotNull($task->fresh()->completed_at);
        $this->assertSame(TaskStatus::Completed, $task->fresh()->status);

        $this->actingAs($this->member)->patch("/tasks/{$task->id}/status", [
            'status' => TaskStatus::InProgress->value,
        ])->assertRedirect();
        $this->assertNull($task->fresh()->completed_at);
    }

    public function test_full_create_and_update_manage_completed_at_consistently(): void
    {
        $this->actingAs($this->member)->post('/tasks', $this->payload([
            'status' => TaskStatus::Completed->value,
        ]))->assertRedirect();
        $task = Task::query()->firstOrFail();
        $this->assertNotNull($task->completed_at);

        $this->actingAs($this->member)->put("/tasks/{$task->id}", $this->payload([
            'status' => TaskStatus::Paused->value,
        ]))->assertRedirect();
        $this->assertNull($task->fresh()->completed_at);
    }

    public function test_authorized_user_soft_deletes_task_and_it_is_hidden(): void
    {
        $task = $this->createTask('تسک حذف‌شدنی', $this->member);

        $this->actingAs($this->manager)->delete("/tasks/{$task->id}")->assertRedirect('/tasks');
        $this->assertSoftDeleted($task);
        $this->actingAs($this->manager)->get('/tasks?scope=all')->assertDontSee('تسک حذف‌شدنی');
    }

    public function test_search_works_by_title_customer_and_assignee(): void
    {
        $customer = Customer::factory()->create(['name' => 'مشتری آفتاب', 'company_name' => 'شرکت سپهر']);
        $target = $this->createTask('طراحی صفحه ویژه', $this->member, ['customer_id' => $customer->id]);
        $other = $this->createTask('کار نامرتبط', $this->manager);

        foreach (['طراحی', 'آفتاب', 'سپهر', $this->member->name] as $query) {
            $this->actingAs($this->manager)->get('/tasks?scope=all&q='.urlencode($query))
                ->assertOk()->assertSee($target->title)->assertDontSee($other->title);
        }
    }

    public function test_status_priority_customer_and_assignee_filters_work(): void
    {
        $customer = Customer::factory()->create();
        $target = $this->createTask('هدف فیلتر', $this->member, [
            'customer_id' => $customer->id,
            'status' => TaskStatus::Review,
            'priority' => TaskPriority::Urgent,
        ]);
        $other = $this->createTask('غیرهدف فیلتر', $this->manager);

        $queries = [
            'status=review',
            'priority=urgent',
            "customer_id={$customer->id}",
            "assignee_id={$this->member->id}",
        ];
        foreach ($queries as $query) {
            $this->actingAs($this->manager)->get("/tasks?scope=all&{$query}")
                ->assertOk()->assertSee($target->title)->assertDontSee($other->title);
        }
    }

    public function test_today_filter_returns_tasks_due_today(): void
    {
        $today = $this->createTask('ددلاین امروز', $this->member, ['due_date' => today()]);
        $tomorrow = $this->createTask('ددلاین فردا', $this->member, ['due_date' => today()->addDay()]);

        $this->actingAs($this->member)->get('/tasks?quick=today')
            ->assertOk()->assertSee($today->title)->assertDontSee($tomorrow->title);
    }

    public function test_overdue_filter_excludes_completed_and_cancelled_tasks(): void
    {
        $overdue = $this->createTask('عقب افتاده باز', $this->member, ['due_date' => today()->subDay()]);
        $completed = $this->createTask('عقب افتاده تکمیل', $this->member, [
            'due_date' => today()->subDay(), 'status' => TaskStatus::Completed, 'completed_at' => now(),
        ]);
        $cancelled = $this->createTask('عقب افتاده لغو', $this->member, [
            'due_date' => today()->subDay(), 'status' => TaskStatus::Cancelled,
        ]);

        $this->actingAs($this->member)->get('/tasks?quick=overdue')
            ->assertOk()->assertSee($overdue->title)
            ->assertDontSee($completed->title)->assertDontSee($cancelled->title);
    }

    public function test_dashboard_uses_real_own_task_metrics_for_normal_user(): void
    {
        $this->createTask('امروز خودم', $this->member, ['due_date' => today()]);
        $this->createTask('عقب خودم', $this->member, ['due_date' => today()->subDay()]);
        $this->createTask('در جریان خودم', $this->member, ['status' => TaskStatus::InProgress]);
        $this->createTask('امروز دیگری', $this->manager, ['due_date' => today()]);

        $this->actingAs($this->member)->get('/dashboard')
            ->assertOk()
            ->assertSeeInOrder(['تسک‌های امروز من', '1', 'عقب‌افتاده من', '1', 'در حال انجام من', '1'])
            ->assertSee('امروز خودم')
            ->assertDontSee('امروز دیگری')
            ->assertDontSee('کل تسک‌های باز');
    }

    public function test_dashboard_view_all_metrics_include_team_data(): void
    {
        $this->createTask('باز تیم', $this->member);
        $this->createTask('عقب تیم', $this->member, ['due_date' => today()->subDay()]);
        $this->createTask('تکمیل تیم', $this->manager, [
            'status' => TaskStatus::Completed, 'completed_at' => now(),
        ]);

        $this->actingAs($this->manager)->get('/dashboard')
            ->assertOk()->assertSee('کل تسک‌های باز')->assertSee('عقب‌افتاده تیم');
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'title' => 'تسک آزمایشی',
            'description' => 'توضیحات تسک',
            'customer_id' => '',
            'assignee_id' => $this->member->id,
            'priority' => TaskPriority::Important->value,
            'status' => TaskStatus::New->value,
            'start_date' => '2026-08-16',
            'due_date' => '2026-08-20',
        ], $overrides);
    }

    private function createTask(string $title, User $assignee, array $attributes = []): Task
    {
        return Task::factory()->create([
            'title' => $title,
            'assignee_id' => $assignee->id,
            'created_by' => $this->manager->id,
            ...$attributes,
        ]);
    }
}
