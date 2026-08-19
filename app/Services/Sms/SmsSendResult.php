<?php

namespace App\Services\Sms;

final readonly class SmsSendResult
{
    public function __construct(
        public bool $success,
        public ?string $messageId = null,
        public ?string $error = null,
    ) {}
}
