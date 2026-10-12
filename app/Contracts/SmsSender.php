<?php

namespace App\Contracts;

interface SmsSender
{
    public function isAvailable(): bool;

    public function send(string $recipient, string $message): void;
}
