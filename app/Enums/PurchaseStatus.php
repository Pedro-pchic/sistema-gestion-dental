<?php

namespace App\Enums;

enum PurchaseStatus: string
{
    case Draft = 'draft';

    case Pending = 'pending';

    case Received = 'received';

    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Pending => 'Pendiente',
            self::Received => 'Recibida',
            self::Cancelled => 'Cancelada',
        };
    }
}
