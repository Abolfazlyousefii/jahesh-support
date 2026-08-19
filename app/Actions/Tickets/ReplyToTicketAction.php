<?php

namespace App\Actions\Tickets;

use App\Enums\TicketMessageType;
use App\Enums\TicketStatus;
use App\Models\Customer;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Services\Activity\ActivityLogger;
use App\Services\Notifications\InAppNotifier;
use App\Services\Sms\SmsNotifier;
use App\Support\TicketWorkflow;
use Illuminate\Support\Facades\DB;

class ReplyToTicketAction
{
    public function __construct(
        private readonly TicketWorkflow $workflow,
        private readonly SmsNotifier $sms,
        private readonly InAppNotifier $notifications,
        private readonly ActivityLogger $activity,
    ) {}

    public function execute(
        Ticket $ticket,
        Customer|User $author,
        string $body,
        TicketMessageType $type,
        ?TicketStatus $statusAfterReply = null,
    ): TicketMessage {
        $previousStatus = $ticket->status;

        $message = DB::transaction(function () use ($ticket, $author, $body, $type, $statusAfterReply) {
            $this->workflow->ensureWritable($ticket);

            $message = $ticket->messages()->create([
                'author_type' => $author->getMorphClass(),
                'author_id' => $author->id,
                'message_type' => $type,
                'body' => $body,
            ]);

            if ($author instanceof Customer) {
                $this->workflow->afterCustomerReply($ticket, $message->created_at);
            } elseif ($type === TicketMessageType::Public) {
                $this->workflow->afterStaffPublicReply($ticket, $message->created_at, $statusAfterReply);
            } else {
                $this->workflow->afterInternalNote($ticket);
            }

            return $message;
        });

        $ticket->refresh();

        if ($previousStatus !== $ticket->status) {
            $this->activity->record(
                $ticket->status === TicketStatus::Closed ? 'ticket.closed' : 'ticket.status_changed',
                $ticket,
                $author,
                $ticket->status === TicketStatus::Closed ? 'تیکت پس از ثبت پاسخ بسته شد.' : 'وضعیت تیکت پس از ثبت پاسخ تغییر کرد.',
                ['status' => $previousStatus],
                ['status' => $ticket->status],
                ['source' => $author instanceof Customer ? 'customer_reply' : 'staff_reply'],
            );
        }

        if ($type === TicketMessageType::Public) {
            if ($author instanceof Customer) {
                $this->sms->ticketCustomerReply($ticket);
                $this->notifications->ticketCustomerReply($ticket);
            } elseif ($statusAfterReply === TicketStatus::Resolved) {
                $this->sms->ticketResolved($ticket);
                $this->notifications->ticketResolved($ticket);
            } else {
                $this->sms->ticketStaffReply($ticket);
                $this->notifications->ticketStaffReply($ticket);
            }
        }

        return $message;
    }
}
