<?php

namespace App\Actions\Tickets;

use App\Models\Ticket;
use App\Support\TicketWorkflow;

class AssignTicketAction
{
    public function __construct(private readonly TicketWorkflow $workflow) {}

    public function execute(Ticket $ticket, int $assigneeId): Ticket
    {
        $this->workflow->ensureWritable($ticket);
        $ticket->update(['assigned_to' => $assigneeId]);

        return $ticket;
    }
}
