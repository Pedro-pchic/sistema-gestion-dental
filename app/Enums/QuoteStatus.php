<?php

namespace App\Enums;

enum QuoteStatus: string
{
    case Draft = 'draft';

    case Sent = 'sent';

    case Accepted = 'accepted';

    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Sent => 'Enviada',
            self::Accepted => 'Aceptada',
            self::Rejected => 'Rechazada',
        };
    }
}
