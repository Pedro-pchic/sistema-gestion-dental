<?php

namespace App\Enums;

enum AppointmentReminderStatus: string
{
    case Pending = 'pending';
    case Queued = 'queued';
    case Sending = 'sending';
    case Sent = 'sent';
    case Failed = 'failed';
    case Skipped = 'skipped';
    case Superseded = 'superseded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Queued => 'En cola',
            self::Sending => 'En proceso',
            self::Sent => 'Enviado',
            self::Failed => 'Fallido',
            self::Skipped => 'Omitido',
            self::Superseded => 'Obsoleto',
        };
    }
}
