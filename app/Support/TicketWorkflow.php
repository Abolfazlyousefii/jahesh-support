<?php

namespace App\Support;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Validation\ValidationException;

final class TicketWorkflow
{
    public function ensureWritable(Ticket $ticket): void
    {
        if ($ticket->status->isClosed()) {
            throw ValidationException::withMessages(['ticket' => 'این درخواست بسته شده و قابل تغییر نیست.']);
        }
    }

    public function afterCustomerReply(Ticket $ticket): void
    {
        if (in_array($ticket->status, [TicketStatus::WaitingCustomer, TicketStatus::Resolved], true)) {
            $ticket->update(['status' => TicketStatus::InReview]);
        } else {
            $ticket->touch();
        }
    }

    public function statusAttributes(Ticket $ticket, TicketStatus $status): array
    {
        $this->ensureWritable($ticket);

        return [
            'status' => $status,
            'closed_at' => $status === TicketStatus::Closed ? now() : null,
        ];
    }
}
