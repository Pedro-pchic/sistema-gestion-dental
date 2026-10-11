<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\SaleStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'customer_id',
    'address_id',
    'quote_id',
    'number',
    'origin',
    'status',
    'logistics_status',
    'estimated_delivery_at',
    'dispatched_at',
    'delivered_at',
    'order_date',
    'notes',
    'total',
])]
class Order extends Model
{
    public const int MAX_TOTAL_CENTS = 99_999_999_999_999;

    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'logistics_status' => OrderStatus::class,
            'order_date' => 'date',
            'total' => 'decimal:2',
            'estimated_delivery_at' => 'datetime',
            'dispatched_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    #[Scope]
    protected function eligibleForLogistics(Builder $query): void
    {
        $query
            ->where('status', OrderStatus::Completed)
            ->whereHas('sale', fn (Builder $saleQuery): Builder => $saleQuery->confirmed());
    }

    #[Scope]
    protected function delayed(Builder $query): void
    {
        $query
            ->whereNotNull('estimated_delivery_at')
            ->where('estimated_delivery_at', '<', now())
            ->where('status', '<>', OrderStatus::Cancelled)
            ->whereDoesntHave('sale', fn (Builder $saleQuery): Builder => $saleQuery->where('status', SaleStatus::Cancelled))
            ->where(function (Builder $statusQuery): void {
                $statusQuery
                    ->whereNull('logistics_status')
                    ->orWhereNotIn('logistics_status', [OrderStatus::Delivered, OrderStatus::Cancelled]);
            });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function sale(): HasOne
    {
        return $this->hasOne(Sale::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)
            ->orderBy('occurred_at')
            ->orderBy('id');
    }

    public function isEditable(): bool
    {
        return $this->status === OrderStatus::Pending;
    }

    public function currentStatus(): OrderStatus
    {
        if (
            $this->status === OrderStatus::Cancelled
            || ($this->relationLoaded('sale') && $this->sale?->status === SaleStatus::Cancelled)
        ) {
            return OrderStatus::Cancelled;
        }

        if ($this->logistics_status instanceof OrderStatus) {
            return $this->logistics_status;
        }

        return $this->status === OrderStatus::Completed
            ? OrderStatus::Confirmed
            : $this->status;
    }

    public function isDelayed(): bool
    {
        return $this->estimated_delivery_at?->isPast() === true
            && ! in_array($this->currentStatus(), [OrderStatus::Delivered, OrderStatus::Cancelled], true);
    }

    public function recordStatusHistory(
        OrderStatus $status,
        ?User $changedBy = null,
        ?string $note = null,
    ): OrderStatusHistory {
        return $this->statusHistories()->create([
            'status' => $status,
            'changed_by' => $changedBy?->getKey(),
            'occurred_at' => now(),
            'note' => $note,
        ]);
    }

    public function transitionTo(OrderStatus $status, ?User $changedBy = null): void
    {
        DB::transaction(function () use ($status, $changedBy): void {
            $order = self::query()
                ->whereKey($this->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($status === OrderStatus::Confirmed) {
                $customer = Customer::query()
                    ->whereKey($order->customer_id)
                    ->lockForUpdate()
                    ->first();

                if ($order->status !== OrderStatus::Pending || ! $customer?->is_active) {
                    throw ValidationException::withMessages([
                        'order' => 'Solo un pedido pendiente con cliente activo puede confirmarse.',
                    ]);
                }

                if (! $order->details()->exists()) {
                    throw ValidationException::withMessages([
                        'order' => 'No se puede confirmar un pedido sin productos.',
                    ]);
                }
            } elseif (
                $status !== OrderStatus::Cancelled
                || ! in_array($order->status, [OrderStatus::Pending, OrderStatus::Confirmed], true)
                || $order->sale()->exists()
            ) {
                throw ValidationException::withMessages([
                    'order' => 'El estado actual del pedido no permite esta acción.',
                ]);
            }

            $order->update(['status' => $status]);
            $order->recordStatusHistory($status, $changedBy);
        });
    }
}
