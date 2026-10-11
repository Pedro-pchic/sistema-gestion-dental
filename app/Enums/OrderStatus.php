<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';

    case Confirmed = 'confirmed';

    case Cancelled = 'cancelled';

    case Completed = 'completed';

    case Preparing = 'preparing';

    case Packed = 'packed';

    case Dispatched = 'dispatched';

    case InTransit = 'in_transit';

    case Delivered = 'delivered';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Confirmed => 'Confirmado',
            self::Cancelled => 'Cancelado',
            self::Completed => 'Completado',
            self::Preparing => 'En preparación',
            self::Packed => 'Empacado',
            self::Dispatched => 'Despachado',
            self::InTransit => 'En tránsito',
            self::Delivered => 'Entregado',
        };
    }

    /**
     * @return array<int, self>
     */
    public static function logisticsStages(): array
    {
        return [
            self::Confirmed,
            self::Preparing,
            self::Packed,
            self::Dispatched,
            self::InTransit,
            self::Delivered,
        ];
    }

    public function nextLogisticsStage(): ?self
    {
        return match ($this) {
            self::Confirmed => self::Preparing,
            self::Preparing => self::Packed,
            self::Packed => self::Dispatched,
            self::Dispatched => self::InTransit,
            self::InTransit => self::Delivered,
            default => null,
        };
    }
}
