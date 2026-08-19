<?php

namespace App\Http\Controllers;

use App\Enums\LedgerEntryType;
use App\Enums\PaymentReceiptStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\CustomerLedgerEntry;
use App\Models\CustomerPaymentReceipt;
use App\Models\Role;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use App\Support\DatePresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(DatePresenter $dates): View
    {
        $user = request()->user();

        $canViewCustomers = $user->can('customers.view');
        $canViewTasks = $user->can('tasks.view');
        $canViewAllTasks = $user->can('tasks.view_all');
        $canViewTickets = $user->can('tickets.view');
        $canViewAllTickets = $user->can('tickets.view_all');
        $canViewFinance = $user->can('finance.view');
        $canViewActivity = $user->can('activity.view');

        $taskMetrics = null;
        $taskStatusMetrics = null;
        $todayTasks = collect();
        $priorityTasks = collect();

        if ($canViewTasks) {
            $taskMetrics = [
                'today' => Task::query()->assignedTo($user)->whereDate('due_date', today())->active()->count(),
                'overdue' => Task::query()->assignedTo($user)->overdue()->count(),
                'inProgress' => Task::query()->assignedTo($user)->where('status', TaskStatus::InProgress->value)->count(),
                'teamOpen' => $canViewAllTasks ? Task::query()->active()->count() : null,
                'teamOverdue' => $canViewAllTasks ? Task::query()->overdue()->count() : null,
            ];

            $taskStatusQuery = fn (): Builder => Task::query()->visibleTo($user);
            $taskStatusMetrics = [
                TaskStatus::New->value => $taskStatusQuery()->where('status', TaskStatus::New->value)->count(),
                TaskStatus::Pending->value => $taskStatusQuery()->where('status', TaskStatus::Pending->value)->count(),
                TaskStatus::InProgress->value => $taskStatusQuery()->where('status', TaskStatus::InProgress->value)->count(),
                TaskStatus::Review->value => $taskStatusQuery()->where('status', TaskStatus::Review->value)->count(),
            ];

            $todayTasks = Task::query()
                ->with(['customer', 'assignee'])
                ->assignedTo($user)
                ->whereDate('due_date', today())
                ->active()
                ->latest()
                ->limit(5)
                ->get();

            $priorityTasks = Task::query()
                ->with(['customer', 'assignee'])
                ->visibleTo($user)
                ->active()
                ->orderByRaw('priority = ? DESC', [TaskPriority::Urgent->value])
                ->orderByRaw('priority = ? DESC', [TaskPriority::Important->value])
                ->orderByRaw('due_date IS NULL ASC')
                ->orderBy('due_date')
                ->limit(5)
                ->get();
        }

        $ticketMetrics = null;
        $attentionTickets = collect();
        $unassignedTickets = collect();
        $needsResponseTickets = collect();

        if ($canViewTickets) {
            $visibleTickets = fn (): Builder => Ticket::query()->visibleTo($user);
            $needsResponseQuery = fn (): Builder => $visibleTickets()
                ->open()
                ->whereNotNull('last_customer_message_at')
                ->where(function (Builder $query) {
                    $query->whereNull('last_staff_message_at')
                        ->orWhereColumn('last_customer_message_at', '>', 'last_staff_message_at');
                });

            $ticketMetrics = [
                'new' => $visibleTickets()->where('status', TicketStatus::New->value)->count(),
                'open' => $visibleTickets()->open()->count(),
                'waiting' => $visibleTickets()->where('status', TicketStatus::WaitingCustomer->value)->count(),
                'inProgress' => $visibleTickets()->where('status', TicketStatus::InProgress->value)->count(),
                'needsResponse' => $needsResponseQuery()->count(),
                'unassigned' => $canViewAllTickets
                    ? Ticket::query()->open()->whereNull('assigned_to')->count()
                    : null,
            ];

            $attentionTickets = $visibleTickets()
                ->with(['customer', 'assignee'])
                ->open()
                ->orderByRaw('priority = ? DESC', [TicketPriority::Urgent->value])
                ->orderByRaw('status = ? DESC', [TicketStatus::New->value])
                ->orderByRaw('status = ? DESC', [TicketStatus::InReview->value])
                ->latest()
                ->limit(5)
                ->get();

            $needsResponseTickets = $needsResponseQuery()
                ->with(['customer', 'assignee'])
                ->latest('last_customer_message_at')
                ->limit(3)
                ->get();

            if ($canViewAllTickets) {
                $unassignedTickets = Ticket::query()
                    ->with('customer')
                    ->open()
                    ->whereNull('assigned_to')
                    ->latest()
                    ->limit(3)
                    ->get();
            }
        }

        [$financeMetrics, $pendingReceipts, $topDebtors] = $canViewFinance
            ? $this->financeData()
            : [null, collect(), collect()];

        $recentActivity = $canViewActivity
            ? ActivityLog::query()->latest()->limit(6)->get()
            : collect();

        $actionItems = $this->buildActionItems(
            $dates,
            $canViewFinance ? $pendingReceipts : collect(),
            $canViewAllTickets ? $unassignedTickets : collect(),
            $canViewTickets ? $needsResponseTickets : collect(),
            $canViewTasks
                ? Task::query()->with(['customer', 'assignee'])->visibleTo($user)->overdue()->orderBy('due_date')->limit(3)->get()
                : collect(),
        );

        return view('dashboard', [
            'activeUsers' => User::query()->where('is_active', true)->count(),
            'rolesCount' => Role::query()->count(),
            'today' => $dates->today(),
            'activeCustomers' => $canViewCustomers ? Customer::query()->active()->count() : null,
            'taskMetrics' => $taskMetrics,
            'taskStatusMetrics' => $taskStatusMetrics,
            'todayTasks' => $todayTasks,
            'priorityTasks' => $priorityTasks,
            'ticketMetrics' => $ticketMetrics,
            'attentionTickets' => $attentionTickets,
            'financeMetrics' => $financeMetrics,
            'topDebtors' => $topDebtors,
            'recentActivity' => $recentActivity,
            'actionItems' => $actionItems,
            'canViewAllTasks' => $canViewAllTasks,
            'canViewAllTickets' => $canViewAllTickets,
        ]);
    }

    /**
     * @return array{0:array<string,int>,1:Collection<int,CustomerPaymentReceipt>,2:Collection<int,array{customer:Customer|null,balance:int}>}
     */
    private function financeData(): array
    {
        $pendingReceipts = CustomerPaymentReceipt::query()
            ->with('customer')
            ->pending()
            ->latest()
            ->limit(4)
            ->get();

        $pendingCount = CustomerPaymentReceipt::query()->pending()->count();
        $pendingAmount = (int) CustomerPaymentReceipt::query()->pending()->sum('amount');
        $approvedTodayCount = CustomerPaymentReceipt::query()
            ->where('status', PaymentReceiptStatus::Approved->value)
            ->whereDate('reviewed_at', today())
            ->count();
        $approvedTodayAmount = (int) CustomerPaymentReceipt::query()
            ->where('status', PaymentReceiptStatus::Approved->value)
            ->whereDate('reviewed_at', today())
            ->sum('amount');

        $balances = CustomerLedgerEntry::query()
            ->effective()
            ->select('customer_id')
            ->selectRaw('SUM(CASE WHEN type = ? THEN amount ELSE 0 END) AS debit_total', [LedgerEntryType::Debit->value])
            ->selectRaw('SUM(CASE WHEN type = ? THEN amount ELSE 0 END) AS credit_total', [LedgerEntryType::Credit->value])
            ->groupBy('customer_id')
            ->get()
            ->map(fn ($row) => [
                'customer_id' => (int) $row->customer_id,
                'balance' => (int) $row->debit_total - (int) $row->credit_total,
            ])
            ->filter(fn (array $row): bool => $row['balance'] > 0)
            ->sortByDesc('balance')
            ->values();

        $customerMap = Customer::query()
            ->whereIn('id', $balances->take(3)->pluck('customer_id'))
            ->get()
            ->keyBy('id');

        $topDebtors = $balances->take(3)->map(fn (array $row) => [
            'customer' => $customerMap->get($row['customer_id']),
            'balance' => $row['balance'],
        ]);

        return [[
            'pendingReceipts' => $pendingCount,
            'pendingAmount' => $pendingAmount,
            'debtors' => $balances->count(),
            'claims' => (int) $balances->sum('balance'),
            'approvedTodayCount' => $approvedTodayCount,
            'approvedTodayAmount' => $approvedTodayAmount,
        ], $pendingReceipts, $topDebtors];
    }

    /** @return Collection<int,array<string,mixed>> */
    private function buildActionItems(
        DatePresenter $dates,
        Collection $pendingReceipts,
        Collection $unassignedTickets,
        Collection $needsResponseTickets,
        Collection $overdueTasks,
    ): Collection {
        $items = collect();

        foreach ($pendingReceipts->take(2) as $receipt) {
            $items->push([
                'tone' => 'purple',
                'icon' => 'finance',
                'title' => 'فیش پرداخت '.$receipt->customer?->name.' منتظر بررسی است',
                'description' => number_format((int) $receipt->amount).' تومان',
                'url' => route('finance.receipts.show', $receipt),
                'action' => 'بررسی فیش',
                'time' => $dates->dateTime($receipt->created_at),
                'rank' => 10,
            ]);
        }

        foreach ($unassignedTickets->take(2) as $ticket) {
            $items->push([
                'tone' => 'orange',
                'icon' => 'tickets',
                'title' => 'تیکت #'.$ticket->id.' هنوز مسئول ندارد',
                'description' => $ticket->subject.' · '.$ticket->customer?->name,
                'url' => route('tickets.show', $ticket),
                'action' => 'تعیین مسئول',
                'time' => $dates->dateTime($ticket->created_at),
                'rank' => 20,
            ]);
        }

        $unassignedIds = $unassignedTickets->pluck('id');
        foreach ($needsResponseTickets->reject(fn ($ticket) => $unassignedIds->contains($ticket->id))->take(2) as $ticket) {
            $items->push([
                'tone' => 'blue',
                'icon' => 'tickets',
                'title' => 'مشتری به تیکت #'.$ticket->id.' پاسخ داده است',
                'description' => $ticket->subject.' · '.$ticket->customer?->name,
                'url' => route('tickets.show', $ticket),
                'action' => 'پاسخ دادن',
                'time' => $dates->dateTime($ticket->last_customer_message_at ?? $ticket->updated_at),
                'rank' => 30,
            ]);
        }

        foreach ($overdueTasks->take(2) as $task) {
            $days = $task->due_date?->diffInDays(today()) ?? 0;
            $items->push([
                'tone' => 'red',
                'icon' => 'tasks',
                'title' => 'تسک #'.$task->id.' از زمان مقرر گذشته است',
                'description' => $task->title.' · مسئول: '.($task->assignee?->name ?? 'بدون مسئول'),
                'url' => route('tasks.show', $task),
                'action' => 'مشاهده تسک',
                'time' => $days > 0 ? number_format($days).' روز تأخیر' : 'سررسید گذشته',
                'rank' => 40,
            ]);
        }

        return $items->sortBy('rank')->take(6)->values();
    }
}
