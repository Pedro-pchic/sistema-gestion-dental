<?php

namespace App\Models;

use App\Enums\PurchaseStatus;
use Database\Factories\PurchaseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

#[Fillable(['supplier_id', 'number', 'status', 'order_date', 'received_at', 'notes', 'total'])]
class Purchase extends Model
{
    public const int MAX_TOTAL_CENTS = 99_999_999_999_999;

    /** @use HasFactory<PurchaseFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => PurchaseStatus::class,
            'order_date' => 'date',
            'received_at' => 'datetime',
            'total' => 'decimal:2',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(PurchaseDetail::class);
    }

    public function expense(): HasOne
    {
        return $this->hasOne(Expense::class);
    }

    public function isEditable(): bool
    {
        return $this->status === PurchaseStatus::Draft;
    }

    public function transitionTo(PurchaseStatus $status): void
    {
        DB::transaction(function () use ($status): void {
            $purchase = self::query()
                ->whereKey($this->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($status === PurchaseStatus::Pending) {
                $supplier = Supplier::query()
                    ->whereKey($purchase->supplier_id)
                    ->lockForUpdate()
                    ->first();

                if ($purchase->status !== PurchaseStatus::Draft || ! $supplier?->is_active) {
                    throw ValidationException::withMessages([
                        'status' => 'Solo un borrador con proveedor activo puede pasar a pendiente.',
                    ]);
                }

                if (! $purchase->details()->exists()) {
                    throw ValidationException::withMessages([
                        'details' => 'La orden debe contener al menos un producto.',
                    ]);
                }
            } elseif (
                $status !== PurchaseStatus::Cancelled
                || ! in_array($purchase->status, [PurchaseStatus::Draft, PurchaseStatus::Pending], true)
            ) {
                throw ValidationException::withMessages([
                    'status' => 'El estado actual de la orden no permite esta acción.',
                ]);
            }

            $purchase->update(['status' => $status]);
        });
    }
}
