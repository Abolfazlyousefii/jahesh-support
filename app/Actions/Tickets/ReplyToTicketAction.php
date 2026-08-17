<?php

namespace App\Actions\Tickets;

use App\Enums\TicketMessageType;
use App\Models\Customer;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Support\TicketWorkflow;
use Illuminate\Support\Facades\DB;

class ReplyToTicketAction
{
    public function __construct(private readonly TicketWorkflow $workflow) {}

    public function execute(Ticket $ticket, Customer|User $author, string $body, TicketMessageType $type): TicketMessage
    {
        return DB::transaction(function () use ($ticket, $author, $body, $type) {
            $this->workflow->ensureWritable($ticket);

            $message = $ticket->messages()->create([
                'author_type' => $author->getMorphClass(),
                'author_id' => $author->id,
                'message_type' => $type,
                'body' => $body,
            ]);

            if ($author instanceof Customer) {
                $this->workflow->afterCustomerReply($ticket);
            } else {
                $ticket->touch();
            }

            return $message;
        });
    }
}
