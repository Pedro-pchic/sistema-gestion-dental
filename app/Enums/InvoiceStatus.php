<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Issued = 'issued';

    case PartiallyPaid = 'partially_paid';

    case Paid = 'paid';

    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Issued => 'Emitida',
            self::PartiallyPaid => 'Parcialmente pagada',
            self::Paid => 'Pagada',
            self::Cancelled => 'Cancelada',
        };
    }
}
