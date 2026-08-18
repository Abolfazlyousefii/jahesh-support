<?php

namespace App\Http\Controllers;

use App\Actions\Tickets\ReplyToTicketAction;
use App\Enums\TicketMessageType;
use App\Enums\TicketStatus;
use App\Http\Requests\Ticket\StoreTicketMessageRequest;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class TicketMessageController extends Controller
{
    public function publicReply(StoreTicketMessageRequest $request, Ticket $ticket, ReplyToTicketAction $action): RedirectResponse
    {
        Gate::authorize('reply', $ticket);

        $statusAfterReply = null;
        if ($request->filled('after_reply_status') && Gate::allows('updateStatus', $ticket)) {
            $statusAfterReply = TicketStatus::from($request->validated('after_reply_status'));
        }

        $action->execute(
            $ticket,
            $request->user(),
            $request->validated('body'),
            TicketMessageType::Public,
            $statusAfterReply,
        );

        return back()->with('success', 'پاسخ برای مشتری ارسال شد.');
    }

    public function internalNote(StoreTicketMessageRequest $request, Ticket $ticket, ReplyToTicketAction $action): RedirectResponse
    {
        Gate::authorize('internalNote', $ticket);
        $action->execute($ticket, $request->user(), $request->validated('body'), TicketMessageType::Internal);

        return back()->with('success', 'یادداشت داخلی ثبت شد.');
    }
}
