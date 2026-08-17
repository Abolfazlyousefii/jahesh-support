<?php

namespace App\Http\Controllers\Portal;

use App\Actions\Tickets\ReplyToTicketAction;
use App\Enums\TicketMessageType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\StoreCustomerTicketReplyRequest;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;

class PortalTicketReplyController extends Controller
{
    public function store(StoreCustomerTicketReplyRequest $request, int $ticket, ReplyToTicketAction $action): RedirectResponse
    {
        $ticket = Ticket::query()
            ->where('customer_id', $request->user('customer')->id)
            ->findOrFail($ticket);
        $action->execute($ticket, $request->user('customer'), $request->validated('body'), TicketMessageType::Public);

        return back()->with('success', 'پیام شما ارسال شد.');
    }
}
