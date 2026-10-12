<?php

namespace App\Enums;

enum DentalPaymentStatus: string
{
    case Registered = 'registered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Registered => 'Registrado',
            self::Cancelled => 'Anulado',
        };
    }
}
