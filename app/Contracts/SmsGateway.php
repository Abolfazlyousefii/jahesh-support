<?php

namespace App\Contracts;

use App\Services\Sms\SmsSendResult;

interface SmsGateway
{
    public function sendPattern(string $to, int $bodyId, array $parameters): SmsSendResult;

    /** @return array{ok:bool,credit:?float,error:?string} */
    public function testConnection(): array;
}
