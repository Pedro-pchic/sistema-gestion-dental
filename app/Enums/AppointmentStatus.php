<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Scheduled = 'scheduled';
    case Confirmed = 'confirmed';
    case InTreatment = 'in_treatment';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Programada',
            self::Confirmed => 'Confirmada',
            self::InTreatment => 'En atención',
            self::Completed => 'Completada',
            self::Cancelled => 'Cancelada',
            self::NoShow => 'No asistió',
        };
    }

    public function colorClasses(): string
    {
        return match ($this) {
            self::Scheduled => 'bg-sky-100 text-sky-800 ring-sky-700/10',
            self::Confirmed => 'bg-brand-100 text-brand-800 ring-brand-700/10',
            self::InTreatment => 'bg-violet-100 text-violet-900 ring-violet-700/10',
            self::Completed => 'bg-emerald-100 text-emerald-800 ring-emerald-700/10',
            self::Cancelled => 'bg-rose-100 text-rose-800 ring-rose-700/10',
            self::NoShow => 'bg-stone-100 text-stone-700 ring-stone-700/10',
        };
    }

    /** @return list<string> */
    public static function reservableValues(): array
    {
        return [self::Scheduled->value, self::Confirmed->value, self::InTreatment->value];
    }

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Scheduled => in_array($next, [self::Confirmed, self::InTreatment, self::Cancelled, self::NoShow], true),
            self::Confirmed => in_array($next, [self::InTreatment, self::Cancelled, self::NoShow], true),
            self::InTreatment => $next === self::Completed,
            self::Completed, self::Cancelled, self::NoShow => false,
        };
    }
}
