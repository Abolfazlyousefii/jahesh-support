<?php

namespace App\Http\Controllers\Portal;

use App\Actions\Tickets\CreateTicketAction;
use App\Enums\TicketMessageType;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\StoreCustomerTicketRequest;
use App\Models\Ticket;
use App\Support\TicketWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalTicketController extends Controller
{
    public function index(Request $request): View
    {
        $customerId = $request->user('customer')->id;

        $ticketStatusCounts = Ticket::query()
            ->where('customer_id', $customerId)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->toBase()
            ->pluck('aggregate', 'status');

        $tickets = Ticket::query()
            ->where('customer_id', $customerId)
            ->with('latestPublicMessage.author')
            ->orderByRaw('CASE WHEN last_staff_message_at IS NOT NULL AND (customer_last_read_at IS NULL OR last_staff_message_at > customer_last_read_at) THEN 0 ELSE 1 END')
            ->latest('updated_at')
            ->paginate(15);

        $activeStatuses = [
            TicketStatus::New,
            TicketStatus::InReview,
            TicketStatus::InProgress,
            TicketStatus::WaitingCustomer,
        ];

        $activeTicketCount = collect($activeStatuses)->sum(
            fn (TicketStatus $status): int => (int) $ticketStatusCounts->get($status->value, 0),
        );

        $finishedTicketCount = (int) $ticketStatusCounts->get(TicketStatus::Resolved->value, 0)
            + (int) $ticketStatusCounts->get(TicketStatus::Closed->value, 0);

        return view('portal.tickets.index', compact(
            'tickets',
            'ticketStatusCounts',
            'activeTicketCount',
            'finishedTicketCount',
        ));
    }

    public function create(): View
    {
        return view('portal.tickets.create', ['priorities' => TicketPriority::cases()]);
    }

    public function store(StoreCustomerTicketRequest $request, CreateTicketAction $action): RedirectResponse
    {
        $ticket = $action->execute($request->user('customer'), $request->validated());

        return redirect()->route('portal.tickets.show', $ticket)->with('success', 'درخواست شما ثبت شد.');
    }

    public function show(Request $request, int $ticket, TicketWorkflow $workflow): View
    {
        $ticket = Ticket::query()
            ->where('customer_id', $request->user('customer')->id)
            ->findOrFail($ticket);

        $workflow->markCustomerRead($ticket);
        $ticket->refresh()->load(['messages' => fn ($query) => $query
            ->where('message_type', TicketMessageType::Public)
            ->with('author')]);

        return view('portal.tickets.show', compact('ticket'));
    }
}
