<?php

namespace App\Http\Controllers;

use App\Actions\Tickets\AssignTicketAction;
use App\Http\Requests\Ticket\AssignTicketRequest;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class TicketAssignmentController extends Controller
{
    public function update(AssignTicketRequest $request, Ticket $ticket, AssignTicketAction $action): RedirectResponse
    {
        Gate::authorize('assign', $ticket);
        $action->execute($ticket, $request->integer('assignee_id'));

        return back()->with('success', 'مسئول تیکت تغییر کرد.');
    }
}
