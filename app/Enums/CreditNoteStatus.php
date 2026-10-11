<?php

namespace App\Enums;

enum CreditNoteStatus: string
{
    case Issued = 'issued';

    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Issued => 'Emitida',
            self::Cancelled => 'Cancelada',
        };
    }
}
