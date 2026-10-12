<?php

namespace App\Enums;

enum TreatmentPlanStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::InProgress => 'En proceso',
            self::Completed => 'Completado',
            self::Cancelled => 'Cancelado',
        };
    }

    public function colorClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-100 text-amber-900 ring-amber-700/10',
            self::InProgress => 'bg-sky-100 text-sky-800 ring-sky-700/10',
            self::Completed => 'bg-emerald-100 text-emerald-800 ring-emerald-700/10',
            self::Cancelled => 'bg-stone-100 text-stone-700 ring-stone-700/10',
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Pending => in_array($next, [self::InProgress, self::Cancelled], true),
            self::InProgress => in_array($next, [self::Completed, self::Cancelled], true),
            self::Completed, self::Cancelled => false,
        };
    }
}
