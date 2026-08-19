<?php

namespace App\Actions\Tickets;

use App\Models\Ticket;
use App\Models\User;
use App\Services\Activity\ActivityLogger;
use App\Services\Notifications\InAppNotifier;
use App\Services\Sms\SmsNotifier;
use App\Support\TicketWorkflow;

class AssignTicketAction
{
    public function __construct(
        private readonly TicketWorkflow $workflow,
        private readonly SmsNotifier $sms,
        private readonly InAppNotifier $notifications,
        private readonly ActivityLogger $activity,
    ) {}

    public function execute(Ticket $ticket, int $assigneeId, ?User $actor = null): Ticket
    {
        $previousAssignee = $ticket->assigned_to;
        $previousStatus = $ticket->status;

        $this->workflow->assign($ticket, $assigneeId);
        $ticket->refresh();

        if ($previousAssignee !== $ticket->assigned_to) {
            $this->activity->record(
                'ticket.assigned',
                $ticket,
                $actor,
                'مسئول تیکت تغییر کرد.',
                ['assignee_id' => $previousAssignee],
                ['assignee_id' => $ticket->assigned_to],
            );

            $this->sms->ticketAssigned($ticket);
            $this->notifications->ticketAssigned($ticket, $actor);
        }

        if ($previousStatus !== $ticket->status) {
            $this->activity->record(
                'ticket.status_changed',
                $ticket,
                $actor,
                'وضعیت تیکت در زمان ارجاع به‌صورت خودکار تغییر کرد.',
                ['status' => $previousStatus],
                ['status' => $ticket->status],
                ['source' => 'assignment'],
            );
        }

        return $ticket;
    }
}
