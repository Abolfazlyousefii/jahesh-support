<?php

namespace App\Actions\Tickets;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Activity\ActivityLogger;
use App\Services\Notifications\InAppNotifier;
use App\Services\Sms\SmsNotifier;
use App\Support\TicketWorkflow;

class UpdateTicketStatusAction
{
    public function __construct(
        private readonly TicketWorkflow $workflow,
        private readonly SmsNotifier $sms,
        private readonly InAppNotifier $notifications,
        private readonly ActivityLogger $activity,
    ) {}

    public function execute(Ticket $ticket, TicketStatus $status, ?User $actor = null): Ticket
    {
        $previous = $ticket->status;
        $ticket->update($this->workflow->statusAttributes($ticket, $status));
        $ticket->refresh();

        if ($previous !== $ticket->status) {
            $this->activity->record(
                $status === TicketStatus::Closed ? 'ticket.closed' : 'ticket.status_changed',
                $ticket,
                $actor,
                $status === TicketStatus::Closed ? 'تیکت بسته شد.' : 'وضعیت تیکت تغییر کرد.',
                ['status' => $previous],
                ['status' => $ticket->status],
            );
        }

        if ($status === TicketStatus::Resolved && $previous !== TicketStatus::Resolved) {
            $this->sms->ticketResolved($ticket);
            $this->notifications->ticketResolved($ticket);
        }

        return $ticket;
    }
}
