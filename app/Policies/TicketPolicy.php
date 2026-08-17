<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function view(User $user, Ticket $ticket): bool
    {
        return $user->can('tickets.view') && ($user->can('tickets.view_all') || $ticket->assigned_to === $user->id);
    }

    public function reply(User $user, Ticket $ticket): bool
    {
        return $user->can('tickets.reply') && $this->view($user, $ticket) && ! $ticket->status->isClosed();
    }

    public function internalNote(User $user, Ticket $ticket): bool
    {
        return $user->can('tickets.internal_notes') && $this->view($user, $ticket) && ! $ticket->status->isClosed();
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        return $user->can('tickets.assign') && $this->view($user, $ticket) && ! $ticket->status->isClosed();
    }

    public function updateStatus(User $user, Ticket $ticket): bool
    {
        return $user->can('tickets.update_status') && $this->view($user, $ticket) && ! $ticket->status->isClosed();
    }

    public function convertToTask(User $user, Ticket $ticket): bool
    {
        return $user->can('tickets.convert_to_task') && $this->view($user, $ticket) && ! $ticket->status->isClosed();
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->can('tickets.delete') && $this->view($user, $ticket);
    }
}
