<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Enums\SaleStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdvanceOrderLogistics
{
    public function handle(Order $order, User $user, ?string $note = null): OrderStatus
    {
        return DB::transaction(function () use ($order, $user, $note): OrderStatus {
            $lockedOrder = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $sale = $lockedOrder->sale()->lockForUpdate()->first();

            if ($lockedOrder->status !== OrderStatus::Completed || $sale?->status !== SaleStatus::Confirmed) {
                throw ValidationException::withMessages([
                    'order' => 'Solo se puede preparar un pedido con venta confirmada.',
                ]);
            }

            $currentStatus = $lockedOrder->logistics_status ?? OrderStatus::Confirmed;
            $nextStatus = $currentStatus->nextLogisticsStage();

            if ($nextStatus === null) {
                throw ValidationException::withMessages([
                    'order' => 'El pedido ya alcanzó el último estado logístico.',
                ]);
            }

            $attributes = ['logistics_status' => $nextStatus];

            if ($nextStatus === OrderStatus::Dispatched) {
                $attributes['dispatched_at'] = now();
            }

            if ($nextStatus === OrderStatus::Delivered) {
                $attributes['delivered_at'] = now();
            }

            $lockedOrder->update($attributes);
            $lockedOrder->recordStatusHistory($nextStatus, $user, $note);

            return $nextStatus;
        }, attempts: 3);
    }
}
