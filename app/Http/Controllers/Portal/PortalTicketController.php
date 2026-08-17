<?php

namespace App\Http\Controllers\Portal;

use App\Actions\Tickets\CreateTicketAction;
use App\Enums\TicketMessageType;
use App\Enums\TicketPriority;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\StoreCustomerTicketRequest;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalTicketController extends Controller
{
    public function index(Request $request): View
    {
        $tickets = Ticket::query()
            ->where('customer_id', $request->user('customer')->id)
            ->latest('updated_at')
            ->paginate(15);

        return view('portal.tickets.index', compact('tickets'));
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

    public function show(Request $request, int $ticket): View
    {
        $ticket = Ticket::query()
            ->where('customer_id', $request->user('customer')->id)
            ->findOrFail($ticket);
        $ticket->load(['messages' => fn ($query) => $query
            ->where('message_type', TicketMessageType::Public)
            ->with('author')]);

        return view('portal.tickets.show', compact('ticket'));
    }
}
