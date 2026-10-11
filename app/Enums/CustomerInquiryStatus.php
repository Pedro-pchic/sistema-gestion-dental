<?php

namespace App\Enums;

enum CustomerInquiryStatus: string
{
    case Pending = 'pending';

    case InProgress = 'in_progress';

    case Answered = 'answered';

    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::InProgress => 'En proceso',
            self::Answered => 'Respondida',
            self::Closed => 'Cerrada',
        };
    }
}
