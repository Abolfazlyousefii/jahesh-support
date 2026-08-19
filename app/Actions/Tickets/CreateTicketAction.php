<?php

namespace App\Actions\Tickets;

use App\Enums\TicketMessageType;
use App\Enums\TicketStatus;
use App\Models\Customer;
use App\Models\Ticket;
use App\Services\Activity\ActivityLogger;
use App\Services\Notifications\InAppNotifier;
use App\Services\Sms\SmsNotifier;
use Illuminate\Support\Facades\DB;

class CreateTicketAction
{
    public function __construct(
        private readonly SmsNotifier $sms,
        private readonly InAppNotifier $notifications,
        private readonly ActivityLogger $activity,
    ) {}

    public function execute(Customer $customer, array $data): Ticket
    {
        $ticket = DB::transaction(function () use ($customer, $data) {
            $now = now();

            $ticket = Ticket::query()->create([
                'customer_id' => $customer->id,
                'subject' => $data['subject'],
                'priority' => $data['priority'],
                'status' => TicketStatus::New,
                'last_customer_message_at' => $now,
                'customer_last_read_at' => $now,
            ]);

            $ticket->messages()->create([
                'author_type' => $customer->getMorphClass(),
                'author_id' => $customer->id,
                'message_type' => TicketMessageType::Public,
                'body' => $data['description'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return $ticket;
        });

        $this->activity->record(
            'ticket.created',
            $ticket,
            $customer,
            'مشتری یک تیکت جدید ثبت کرد.',
            new: $this->activity->snapshot($ticket, ['subject', 'priority', 'status', 'customer_id']),
        );

        $this->sms->ticketCreated($ticket);
        $this->notifications->ticketCreated($ticket);

        return $ticket;
    }
}
