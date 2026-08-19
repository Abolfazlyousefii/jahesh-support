<?php

namespace App\Http\Controllers;

use App\Actions\Tickets\UpdateTicketStatusAction;
use App\Enums\TicketStatus;
use App\Http\Requests\Ticket\UpdateTicketStatusRequest;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class TicketStatusController extends Controller
{
    public function update(UpdateTicketStatusRequest $request, Ticket $ticket, UpdateTicketStatusAction $action): RedirectResponse
    {
        Gate::authorize('updateStatus', $ticket);
        $action->execute($ticket, TicketStatus::from($request->validated('status')), $request->user());

        return back()->with('success', 'وضعیت تیکت تغییر کرد.');
    }
}
