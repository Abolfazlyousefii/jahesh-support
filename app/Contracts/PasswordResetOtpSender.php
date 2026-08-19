<?php

namespace App\Contracts;

interface PasswordResetOtpSender
{
    public function send(string $phone, string $code): void;
}
