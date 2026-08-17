<?php

namespace Database\Factories;

use App\Enums\TicketMessageType;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TicketMessage> */
class TicketMessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'author_type' => User::class,
            'author_id' => User::factory(),
            'message_type' => TicketMessageType::Public,
            'body' => fake()->paragraph(),
        ];
    }
}
