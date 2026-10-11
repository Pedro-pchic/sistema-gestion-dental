<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';

    case Card = 'card';

    case Transfer = 'transfer';

    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Efectivo',
            self::Card => 'Tarjeta',
            self::Transfer => 'Transferencia',
            self::Other => 'Otro',
        };
    }
}
