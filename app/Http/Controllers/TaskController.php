<?php

namespace App\Http\Controllers;

use App\Actions\Tasks\CreateTaskAction;
use App\Actions\Tasks\UpdateTaskAction;
use App\Actions\Tasks\UpdateTaskStatusAction;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Requests\Task\UpdateTaskStatusRequest;
use App\Models\Customer;
use App\Models\Task;
use App\Models\User;
use App\Services\Activity\ActivityLogger;
use App\Services\Settings\SettingsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $search = trim($request->string('q')->toString());
        $quick = in_array($request->string('quick')->toString(), ['today', 'overdue', 'in_progress', 'completed'], true)
            ? $request->string('quick')->toString()
            : 'all';
        $scope = $user->can('tasks.view_all') && $request->string('scope')->toString() === 'all' ? 'all' : 'mine';
        $priority = TaskPriority::tryFrom($request->string('priority')->toString());
        $status = TaskStatus::tryFrom($request->string('status')->toString());
        $customerId = $request->integer('customer_id') ?: null;
        $assigneeId = $user->can('tasks.view_all') ? ($request->integer('assignee_id') ?: null) : null;

        $requestedView = $request->string('view')->toString();
        $view = $requestedView === 'list' ? 'list' : 'board';

        // وضعیت‌های بایگانی/ثانویه در برد ستون دائمی ندارند؛
        // در صورت فیلتر مستقیم روی آن‌ها، لیست خواناتر و کامل‌تر است.
        if (($status !== null && ! $status->isWorkflow()) || $quick === 'completed') {
            $view = 'list';
        }

        $baseQuery = Task::query()
            ->with(['customer', 'assignee', 'creator'])
            ->when($scope === 'mine', fn (Builder $query) => $query->assignedTo($user))
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn (Builder $customer) => $customer->where(
                            fn (Builder $customer) => $customer->where('name', 'like', "%{$search}%")
                                ->orWhere('company_name', 'like', "%{$search}%"),
                        ))
                        ->orWhereHas('assignee', fn (Builder $assignee) => $assignee->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($quick === 'today', fn (Builder $query) => $query->whereDate('due_date', today()))
            ->when($quick === 'overdue', fn (Builder $query) => $query->overdue())
            ->when($quick === 'in_progress', fn (Builder $query) => $query->where('status', TaskStatus::InProgress))
            ->when($quick === 'completed', fn (Builder $query) => $query->where('status', TaskStatus::Completed))
            ->when($priority, fn (Builder $query) => $query->where('priority', $priority))
            ->when($customerId, fn (Builder $query) => $query->where('customer_id', $customerId))
            ->when($assigneeId, fn (Builder $query) => $query->where('assignee_id', $assigneeId));

        $boardCounts = collect(TaskStatus::cases())
            ->mapWithKeys(fn (TaskStatus $item) => [$item->value => 0]);

        if ($view === 'board') {
            $statusCounts = (clone $baseQuery)
                ->reorder()
                ->selectRaw('status, COUNT(*) as aggregate')
                ->groupBy('status')
                ->toBase()
                ->pluck('aggregate', 'status');

            $boardCounts = $boardCounts->map(
                fn (int $count, string $key) => (int) ($statusCounts[$key] ?? $count),
            );
        }

        $query = (clone $baseQuery)
            ->when($status, fn (Builder $query) => $query->where('status', $status));

        $tasks = null;
        $boardTasks = collect();

        if ($view === 'board') {
            $boardTasks = $query
                ->whereIn('status', TaskStatus::workflowValues())
                ->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'important' THEN 2 ELSE 3 END")
                ->orderByRaw('due_date IS NULL')
                ->orderBy('due_date')
                ->latest('id')
                ->get()
                ->groupBy(fn (Task $task) => $task->status->value);
        } else {
            $tasks = $query
                ->orderByRaw('due_date IS NULL')
                ->orderBy('due_date')
                ->latest('id')
                ->paginate(app(SettingsService::class)->paginationPerPage())
                ->withQueryString();
        }

        $mobileStatus = $this->mobileBoardStatus($request, $status, $boardCounts);

        return view('tasks.index', [
            'tasks' => $tasks,
            'boardTasks' => $boardTasks,
            'boardCounts' => $boardCounts,
            'workflowStatuses' => TaskStatus::workflow(),
            'secondaryStatuses' => TaskStatus::secondary(),
            'mobileStatus' => $mobileStatus,
            'view' => $view,
            'search' => $search,
            'quick' => $quick,
            'scope' => $scope,
            'priority' => $priority?->value,
            'status' => $status?->value,
            'customerId' => $customerId,
            'assigneeId' => $assigneeId,
            'priorities' => TaskPriority::cases(),
            'statuses' => TaskStatus::cases(),
            'statusLabels' => collect(TaskStatus::cases())
                ->mapWithKeys(fn (TaskStatus $item) => [$item->value => $item->label()])
                ->all(),
            'customers' => Customer::query()->active()->orderBy('name')->get(['id', 'name', 'company_name']),
            'assignees' => $user->can('tasks.view_all') ? $this->activeAssignees() : collect(),
            'canAssign' => $user->can('tasks.assign'),
        ]);
    }

    public function create(Request $request): View
    {
        return view('tasks.create', $this->formData($request->user()));
    }

    public function store(StoreTaskRequest $request, CreateTaskAction $action): RedirectResponse|JsonResponse
    {
        $task = $action->execute($request->user(), $request->validated());

        if ($request->expectsJson()) {
            $task->load(['customer', 'assignee.roles', 'creator']);

            $scope = $request->user()->can('tasks.view_all')
                && $request->string('scope')->toString() === 'all'
                    ? 'all'
                    : 'mine';

            $visibleOnCurrentBoard = $scope === 'all' || $task->assignee_id === $request->user()->id;

            return response()->json([
                'message' => 'تسک با موفقیت ایجاد شد.',
                'task_id' => $task->id,
                'status' => $task->status->value,
                'visible_on_current_board' => $visibleOnCurrentBoard,
                'desktop_html' => view('tasks._card', [
                    'task' => $task,
                    'scope' => $scope,
                    'statuses' => TaskStatus::cases(),
                    'mode' => 'desktop',
                ])->render(),
                'mobile_html' => view('tasks._card', [
                    'task' => $task,
                    'scope' => $scope,
                    'statuses' => TaskStatus::cases(),
                    'mode' => 'mobile',
                ])->render(),
            ], 201);
        }

        return redirect()->route('tasks.show', $task)->with('success', 'تسک با موفقیت ایجاد شد.');
    }

    public function show(Request $request, Task $task): View
    {
        Gate::authorize('view', $task);
        $task->load(['customer', 'assignee.roles', 'creator', 'sourceTicket']);

        return view('tasks.show', ['task' => $task, 'statuses' => TaskStatus::cases()]);
    }

    public function edit(Request $request, Task $task): View
    {
        Gate::authorize('update', $task);
        $task->load(['customer', 'assignee']);

        return view('tasks.edit', ['task' => $task, ...$this->formData($request->user(), $task)]);
    }

    public function update(UpdateTaskRequest $request, Task $task, UpdateTaskAction $action): RedirectResponse
    {
        Gate::authorize('update', $task);
        $action->execute($request->user(), $task, $request->validated());

        return redirect()->route('tasks.show', $task)->with('success', 'تسک به‌روزرسانی شد.');
    }

    public function updateStatus(
        UpdateTaskStatusRequest $request,
        Task $task,
        UpdateTaskStatusAction $action,
    ): RedirectResponse|JsonResponse {
        Gate::authorize('updateStatus', $task);

        $status = TaskStatus::from($request->validated('status'));
        $action->execute($task, $status, $request->user());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'وضعیت تسک تغییر کرد.',
                'task_id' => $task->id,
                'status' => $status->value,
                'status_label' => $status->label(),
                'completed_at' => $task->fresh()->completed_at?->toIso8601String(),
            ]);
        }

        return back()->with('success', 'وضعیت تسک تغییر کرد.');
    }

    public function destroy(Request $request, Task $task, ActivityLogger $activity): RedirectResponse
    {
        Gate::authorize('delete', $task);

        $activity->record(
            'task.deleted',
            $task,
            $request->user(),
            'تسک حذف شد.',
            old: $activity->snapshot($task, ['title', 'customer_id', 'assignee_id', 'priority', 'status', 'due_date']),
        );

        $task->delete();

        return redirect()->route('tasks.index')->with('success', 'تسک حذف شد.');
    }

    private function formData(User $user, ?Task $task = null): array
    {
        $customers = Customer::query()->active()->orderBy('name')->get(['id', 'name', 'company_name']);
        if ($task?->customer !== null && ! $customers->contains('id', $task->customer->id)) {
            $customers->prepend($task->customer);
        }

        return [
            'customers' => $customers,
            'assignees' => $user->can('tasks.assign') ? $this->activeAssignees() : collect([$user]),
            'priorities' => TaskPriority::cases(),
            'statuses' => TaskStatus::cases(),
            'canAssign' => $user->can('tasks.assign'),
        ];
    }

    private function activeAssignees(): Collection
    {
        return User::query()->with('roles')->where('is_active', true)->orderBy('name')->get();
    }

    private function mobileBoardStatus(Request $request, ?TaskStatus $filteredStatus, SupportCollection $counts): string
    {
        $requested = TaskStatus::tryFrom($request->string('column')->toString());

        if ($requested?->isWorkflow()) {
            return $requested->value;
        }

        if ($filteredStatus?->isWorkflow()) {
            return $filteredStatus->value;
        }

        foreach ([TaskStatus::InProgress, TaskStatus::Review, TaskStatus::Pending, TaskStatus::New] as $status) {
            if (($counts[$status->value] ?? 0) > 0) {
                return $status->value;
            }
        }

        return TaskStatus::New->value;
    }
}
