<?php

namespace App\Actions\Tickets;

use App\Models\Ticket;
use App\Services\Sms\SmsNotifier;
use App\Support\TicketWorkflow;

class AssignTicketAction
{
    public function __construct(
        private readonly TicketWorkflow $workflow,
        private readonly SmsNotifier $sms,
    ) {}

    public function execute(Ticket $ticket, int $assigneeId): Ticket
    {
        $previousAssignee = $ticket->assigned_to;
        $this->workflow->assign($ticket, $assigneeId);
        $ticket->refresh();

        if ($previousAssignee !== $ticket->assigned_to) {
            $this->sms->ticketAssigned($ticket);
        }

        return $ticket;
    }
}
