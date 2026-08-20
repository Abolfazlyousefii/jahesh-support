<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_sidebar_groups_and_submenus_follow_permissions(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo([
            'dashboard.view',
            'tasks.view',
            'tasks.create',
            'tickets.view',
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('class="sidebar-nav"', false)
            ->assertSee('تسک‌های من')
            ->assertSee('ایجاد تسک')
            ->assertSee('کانبان')
            ->assertSee('تیکت‌های من')
            ->assertSee('نیازمند پاسخ')
            ->assertDontSee('مالی مشتریان')
            ->assertDontSee('تنظیمات عمومی');
    }

    public function test_active_task_route_opens_parent_and_marks_matching_submenu(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['tasks.view', 'tasks.create']);

        $this->actingAs($user)
            ->get('/tasks/create')
            ->assertOk()
            ->assertSee("pinnedMenu: 'tasks'", false)
            ->assertSee('aria-current="page"', false)
            ->assertSee('ایجاد تسک');
    }

    public function test_sidebar_task_badge_counts_only_overdue_visible_tasks(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['dashboard.view', 'tasks.view']);

        Task::factory()->create([
            'assignee_id' => $user->id,
            'created_by' => $user->id,
            'status' => TaskStatus::InProgress,
            'due_date' => today()->subDay(),
        ]);
        Task::factory()->create([
            'assignee_id' => $user->id,
            'created_by' => $user->id,
            'status' => TaskStatus::InProgress,
            'due_date' => today()->addDay(),
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('sidebar-badge-danger', false)
            ->assertSee('data-count="1"', false);
    }
}
