<?php

namespace App\Http\Controllers;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Customer;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Activity\ActivityLogger;
use App\Support\PhoneNormalizer;
use App\Support\TicketWorkflow;
use App\Services\Settings\SettingsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $canViewAll = $user->can('tickets.view_all');
        $scope = $canViewAll && $request->string('scope')->toString() === 'mine' ? 'mine' : ($canViewAll ? 'all' : 'mine');
        $search = trim($request->string('q')->toString());
        $normalizedSearch = PhoneNormalizer::normalize($search);
        $quick = TicketStatus::tryFrom($request->string('quick')->toString());
        $status = TicketStatus::tryFrom($request->string('status')->toString());
        $priority = TicketPriority::tryFrom($request->string('priority')->toString());
        $assigneeFilter = $canViewAll ? $request->string('assignee_id')->toString() : '';
        $unassignedOnly = $assigneeFilter === 'unassigned';
        $assigneeId = $canViewAll && ctype_digit($assigneeFilter) && (int) $assigneeFilter > 0
            ? (int) $assigneeFilter
            : null;
        $customerId = $request->integer('customer_id') ?: null;
        $unreadOnly = $request->boolean('unread');

        $baseQuery = Ticket::query()
            ->with(['customer.primaryPhone', 'assignee', 'latestPublicMessage.author'])
            ->when($scope === 'mine', fn (Builder $query) => $query->assignedTo($user))
            ->when($search !== '', function (Builder $query) use ($search, $normalizedSearch) {
                $query->where(function (Builder $query) use ($search, $normalizedSearch) {
                    $query->where('subject', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn (Builder $customer) => $customer
                            ->where(fn (Builder $customer) => $customer->where('name', 'like', "%{$search}%")
                                ->orWhere('company_name', 'like', "%{$search}%"))
                            ->when($normalizedSearch !== '', fn (Builder $customer) => $customer->orWhereHas(
                                'phones',
                                fn (Builder $phone) => $phone->where('phone', 'like', "%{$normalizedSearch}%"),
                            )));
                });
            })
            ->when($priority, fn (Builder $query) => $query->where('priority', $priority))
            ->when($unassignedOnly, fn (Builder $query) => $query->whereNull('assigned_to'))
            ->when($assigneeId, fn (Builder $query) => $query->where('assigned_to', $assigneeId))
            ->when($customerId, fn (Builder $query) => $query->where('customer_id', $customerId));

        $statusCounts = collect(TicketStatus::cases())->mapWithKeys(fn (TicketStatus $item) => [$item->value => 0]);
        $rawCounts = (clone $baseQuery)
            ->reorder()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->toBase()
            ->pluck('aggregate', 'status');
        $statusCounts = $statusCounts->map(fn (int $count, string $key) => (int) ($rawCounts[$key] ?? $count));
        $unreadCount = (clone $baseQuery)->unreadForStaff()->count();

        $tickets = (clone $baseQuery)
            ->when($quick, fn (Builder $query) => $query->where('status', $quick))
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->when($unreadOnly, fn (Builder $query) => $query->unreadForStaff())
            ->orderByRaw('CASE WHEN last_customer_message_at IS NOT NULL AND (assignee_last_read_at IS NULL OR last_customer_message_at > assignee_last_read_at) THEN 0 ELSE 1 END')
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'important' THEN 2 ELSE 3 END")
            ->latest('updated_at')
            ->paginate(app(SettingsService::class)->paginationPerPage())
            ->withQueryString();

        return view('tickets.index', [
            'tickets' => $tickets,
            'search' => $search,
            'quick' => $quick?->value,
            'status' => $status?->value,
            'priority' => $priority?->value,
            'assigneeId' => $assigneeId,
            'unassignedOnly' => $unassignedOnly,
            'customerId' => $customerId,
            'scope' => $scope,
            'unreadOnly' => $unreadOnly,
            'unreadCount' => $unreadCount,
            'statusCounts' => $statusCounts,
            'statuses' => TicketStatus::cases(),
            'priorities' => TicketPriority::cases(),
            'assignees' => $canViewAll ? $this->activeAssignees() : collect(),
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Request $request, Ticket $ticket, TicketWorkflow $workflow): View
    {
        Gate::authorize('view', $ticket);
        $workflow->markStaffRead($ticket, $request->user());
        $ticket->refresh()->load(['customer.primaryPhone', 'assignee', 'messages.author', 'task.assignee']);

        return view('tickets.show', [
            'ticket' => $ticket,
            'statuses' => TicketStatus::cases(),
            'assignees' => $this->activeAssignees(),
        ]);
    }

    public function destroy(Request $request, Ticket $ticket, ActivityLogger $activity): RedirectResponse
    {
        Gate::authorize('delete', $ticket);

        $activity->record(
            'ticket.deleted',
            $ticket,
            $request->user(),
            'تیکت حذف شد.',
            old: $activity->snapshot($ticket, ['subject', 'customer_id', 'assigned_to', 'priority', 'status']),
        );

        $ticket->delete();

        return redirect()->route('tickets.index')->with('success', 'تیکت حذف شد.');
    }

    private function activeAssignees(): Collection
    {
        return User::query()->where('is_active', true)->orderBy('name')->get();
    }
}
