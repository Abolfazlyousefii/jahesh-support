<?php

namespace App\Enums;

enum TicketMessageType: string
{
    case Public = 'public';
    case Internal = 'internal';
}
