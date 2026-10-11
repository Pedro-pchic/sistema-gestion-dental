<?php

namespace App\Support;

use InvalidArgumentException;

class Money
{
    public static function toCents(string|int $amount): int
    {
        $amount = (string) $amount;

        if (! preg_match('/^(-?)(\d+)(?:\.(\d{1,2}))?$/D', $amount, $matches)) {
            throw new InvalidArgumentException('The amount must be a decimal with up to two fractional digits.');
        }

        $fraction = str_pad($matches[3] ?? '', 2, '0');
        $cents = ((int) $matches[2] * 100) + (int) $fraction;

        return ($matches[1] ?? '') === '-' ? -$cents : $cents;
    }

    public static function fromCents(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $absoluteCents = abs($cents);

        return $sign.intdiv($absoluteCents, 100).'.'.str_pad((string) ($absoluteCents % 100), 2, '0', STR_PAD_LEFT);
    }
}
