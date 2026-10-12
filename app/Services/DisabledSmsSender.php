<?php

namespace App\Services;

use App\Contracts\SmsSender;
use LogicException;

class DisabledSmsSender implements SmsSender
{
    public function isAvailable(): bool
    {
        return false;
    }

    public function send(string $recipient, string $message): void
    {
        throw new LogicException('SMS delivery is disabled because no provider is configured.');
    }
}
