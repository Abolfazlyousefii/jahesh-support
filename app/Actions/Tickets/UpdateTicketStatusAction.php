<?php

namespace App\Actions\Tickets;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Services\Sms\SmsNotifier;
use App\Support\TicketWorkflow;

class UpdateTicketStatusAction
{
    public function __construct(
        private readonly TicketWorkflow $workflow,
        private readonly SmsNotifier $sms,
    ) {}

    public function execute(Ticket $ticket, TicketStatus $status): Ticket
    {
        $previous = $ticket->status;
        $ticket->update($this->workflow->statusAttributes($ticket, $status));
        $ticket->refresh();

        if ($status === TicketStatus::Resolved && $previous !== TicketStatus::Resolved) {
            $this->sms->ticketResolved($ticket);
        }

        return $ticket;
    }
}
