<?php

namespace App\Actions\Tickets;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Support\TicketWorkflow;

class UpdateTicketStatusAction
{
    public function __construct(private readonly TicketWorkflow $workflow) {}

    public function execute(Ticket $ticket, TicketStatus $status): Ticket
    {
        $ticket->update($this->workflow->statusAttributes($ticket, $status));

        return $ticket;
    }
}
