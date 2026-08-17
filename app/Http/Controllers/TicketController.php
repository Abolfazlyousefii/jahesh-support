<?php

namespace App\Http\Controllers;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Customer;
use App\Models\Ticket;
use App\Models\User;
use App\Support\PhoneNormalizer;
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
        $search = trim($request->string('q')->toString());
        $normalizedSearch = PhoneNormalizer::normalize($search);
        $quick = TicketStatus::tryFrom($request->string('quick')->toString());
        $status = TicketStatus::tryFrom($request->string('status')->toString());
        $priority = TicketPriority::tryFrom($request->string('priority')->toString());
        $assigneeId = $user->can('tickets.view_all') ? ($request->integer('assignee_id') ?: null) : null;
        $customerId = $request->integer('customer_id') ?: null;

        $tickets = Ticket::query()
            ->with(['customer.primaryPhone', 'assignee'])
            ->visibleTo($user)
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
            ->when($quick, fn (Builder $query) => $query->where('status', $quick))
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->when($priority, fn (Builder $query) => $query->where('priority', $priority))
            ->when($assigneeId, fn (Builder $query) => $query->where('assigned_to', $assigneeId))
            ->when($customerId, fn (Builder $query) => $query->where('customer_id', $customerId))
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('tickets.index', [
            'tickets' => $tickets,
            'search' => $search,
            'quick' => $quick?->value,
            'status' => $status?->value,
            'priority' => $priority?->value,
            'assigneeId' => $assigneeId,
            'customerId' => $customerId,
            'statuses' => TicketStatus::cases(),
            'priorities' => TicketPriority::cases(),
            'assignees' => $user->can('tickets.view_all') ? $this->activeAssignees() : collect(),
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Ticket $ticket): View
    {
        Gate::authorize('view', $ticket);
        $ticket->load(['customer.primaryPhone', 'assignee', 'messages.author', 'task']);

        return view('tickets.show', [
            'ticket' => $ticket,
            'statuses' => TicketStatus::cases(),
            'assignees' => $this->activeAssignees(),
        ]);
    }

    public function destroy(Ticket $ticket): RedirectResponse
    {
        Gate::authorize('delete', $ticket);
        $ticket->delete();

        return redirect()->route('tickets.index')->with('success', 'تیکت حذف شد.');
    }

    private function activeAssignees(): Collection
    {
        return User::query()->where('is_active', true)->orderBy('name')->get();
    }
}
