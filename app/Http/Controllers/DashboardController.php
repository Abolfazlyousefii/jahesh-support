<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Models\Customer;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use App\Support\DatePresenter;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(DatePresenter $dates): View
    {
        $user = request()->user();
        $canViewCustomers = $user->can('customers.view');
        $canViewTasks = $user->can('tasks.view');
        $canViewAllTasks = $user->can('tasks.view_all');

        return view('dashboard', [
            'activeUsers' => User::query()->where('is_active', true)->count(),
            'rolesCount' => Role::query()->count(),
            'today' => $dates->today(),
            'activeCustomers' => $canViewCustomers ? Customer::query()->active()->count() : null,
            'taskMetrics' => $canViewTasks ? [
                'today' => Task::query()->assignedTo($user)->whereDate('due_date', today())->count(),
                'overdue' => Task::query()->assignedTo($user)->overdue()->count(),
                'inProgress' => Task::query()->assignedTo($user)->where('status', TaskStatus::InProgress)->count(),
                'teamOpen' => $canViewAllTasks ? Task::query()->active()->count() : null,
                'teamOverdue' => $canViewAllTasks ? Task::query()->overdue()->count() : null,
            ] : null,
            'todayTasks' => $canViewTasks
                ? Task::query()->with('customer')->assignedTo($user)->whereDate('due_date', today())->latest()->limit(5)->get()
                : collect(),
        ]);
    }
}
