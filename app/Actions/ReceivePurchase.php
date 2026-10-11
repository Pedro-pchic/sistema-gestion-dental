<?php

namespace App\Actions;

use App\Enums\InventoryMovementType;
use App\Enums\PurchaseStatus;
use App\Models\Inventory;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReceivePurchase
{
    public function handle(Purchase $purchase, User $receiver): Purchase
    {
        return DB::transaction(function () use ($purchase, $receiver): Purchase {
            $lockedPurchase = Purchase::query()
                ->whereKey($purchase->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedPurchase->status !== PurchaseStatus::Pending) {
                throw ValidationException::withMessages([
                    'purchase' => 'Solo una orden pendiente puede recibirse.',
                ]);
            }

            $details = $lockedPurchase->details()
                ->with('product.inventory')
                ->orderBy('product_id')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($details->isEmpty()) {
                throw ValidationException::withMessages([
                    'purchase' => 'No se puede recibir una orden sin productos.',
                ]);
            }

            foreach ($details as $detail) {
                $inventory = $detail->product->inventory;

                if (! $inventory instanceof Inventory) {
                    throw ValidationException::withMessages([
                        'purchase' => "El producto {$detail->product->sku} no tiene inventario inicializado.",
                    ]);
                }

                $inventory->recordMovement(
                    InventoryMovementType::Entry,
                    $detail->quantity,
                    "Recepción de compra {$lockedPurchase->number}",
                    $receiver,
                );
            }

            $lockedPurchase->update([
                'status' => PurchaseStatus::Received,
                'received_at' => now(),
            ]);

            return $lockedPurchase;
        });
    }
}
