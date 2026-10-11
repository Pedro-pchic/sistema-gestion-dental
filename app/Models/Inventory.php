<?php

namespace App\Models;

use App\Enums\InventoryMovementType;
use Database\Factories\InventoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

#[Fillable(['product_id', 'stock', 'minimum_stock'])]
class Inventory extends Model
{
    public const int MAX_STOCK = 2_147_483_647;

    /** @use HasFactory<InventoryFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'stock' => 'integer',
            'minimum_stock' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function recordMovement(
        InventoryMovementType $type,
        int $quantity,
        ?string $reason,
        ?User $user,
    ): InventoryMovement {
        return DB::transaction(function () use ($type, $quantity, $reason, $user): InventoryMovement {
            $inventory = self::query()
                ->whereKey($this->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($type === InventoryMovementType::Adjustment && blank($reason)) {
                throw ValidationException::withMessages([
                    'reason' => 'El motivo es obligatorio para realizar un ajuste.',
                ]);
            }

            if ($quantity < 0 || ($type !== InventoryMovementType::Adjustment && $quantity === 0)) {
                throw ValidationException::withMessages([
                    'quantity' => 'La cantidad debe ser mayor que cero para entradas y salidas, y no puede ser negativa.',
                ]);
            }

            $previousStock = $inventory->stock;
            $resultingStock = match ($type) {
                InventoryMovementType::Entry => $previousStock + $quantity,
                InventoryMovementType::Exit => $previousStock - $quantity,
                InventoryMovementType::Adjustment => $quantity,
            };

            if ($resultingStock < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'La salida no puede superar las existencias disponibles.',
                ]);
            }

            if ($resultingStock > self::MAX_STOCK) {
                throw ValidationException::withMessages([
                    'quantity' => 'La existencia resultante excede el valor máximo permitido.',
                ]);
            }

            $inventory->update(['stock' => $resultingStock]);

            return $inventory->movements()->create([
                'user_id' => $user?->getKey(),
                'type' => $type,
                'quantity' => $type === InventoryMovementType::Adjustment
                    ? $resultingStock - $previousStock
                    : $quantity,
                'previous_stock' => $previousStock,
                'resulting_stock' => $resultingStock,
                'reason' => $reason,
            ]);
        });
    }
}
