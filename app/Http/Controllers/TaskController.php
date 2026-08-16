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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
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

        $tasks = Task::query()
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
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->when($customerId, fn (Builder $query) => $query->where('customer_id', $customerId))
            ->when($assigneeId, fn (Builder $query) => $query->where('assignee_id', $assigneeId))
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('tasks.index', [
            'tasks' => $tasks,
            'search' => $search,
            'quick' => $quick,
            'scope' => $scope,
            'priority' => $priority?->value,
            'status' => $status?->value,
            'customerId' => $customerId,
            'assigneeId' => $assigneeId,
            'priorities' => TaskPriority::cases(),
            'statuses' => TaskStatus::cases(),
            'customers' => Customer::query()->active()->orderBy('name')->get(['id', 'name', 'company_name']),
            'assignees' => $user->can('tasks.view_all') ? $this->activeAssignees() : collect(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('tasks.create', $this->formData($request->user()));
    }

    public function store(StoreTaskRequest $request, CreateTaskAction $action): RedirectResponse
    {
        $task = $action->execute($request->user(), $request->validated());

        return redirect()->route('tasks.show', $task)->with('success', 'تسک با موفقیت ایجاد شد.');
    }

    public function show(Request $request, Task $task): View
    {
        Gate::authorize('view', $task);
        $task->load(['customer', 'assignee.roles', 'creator']);

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

    public function updateStatus(UpdateTaskStatusRequest $request, Task $task, UpdateTaskStatusAction $action): RedirectResponse
    {
        Gate::authorize('updateStatus', $task);
        $action->execute($task, TaskStatus::from($request->validated('status')));

        return back()->with('success', 'وضعیت تسک تغییر کرد.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        Gate::authorize('delete', $task);
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
}
