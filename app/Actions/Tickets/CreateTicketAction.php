<?php

namespace App\Actions\Tickets;

use App\Enums\TicketMessageType;
use App\Enums\TicketStatus;
use App\Models\Customer;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

class CreateTicketAction
{
    public function execute(Customer $customer, array $data): Ticket
    {
        return DB::transaction(function () use ($customer, $data) {
            $ticket = Ticket::query()->create([
                'customer_id' => $customer->id,
                'subject' => $data['subject'],
                'priority' => $data['priority'],
                'status' => TicketStatus::New,
            ]);

            $ticket->messages()->create([
                'author_type' => $customer->getMorphClass(),
                'author_id' => $customer->id,
                'message_type' => TicketMessageType::Public,
                'body' => $data['description'],
            ]);

            return $ticket;
        });
    }
}
