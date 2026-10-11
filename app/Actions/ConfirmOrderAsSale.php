<?php

namespace App\Actions;

use App\Enums\InventoryMovementType;
use App\Enums\OrderStatus;
use App\Enums\SaleStatus;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ConfirmOrderAsSale
{
    public function handle(Order $order, User $user): Sale
    {
        return DB::transaction(function () use ($order, $user): Sale {
            $lockedOrder = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedOrder->status !== OrderStatus::Confirmed || $lockedOrder->sale()->exists()) {
                throw ValidationException::withMessages([
                    'order' => 'Solo un pedido confirmado sin venta previa puede registrarse como venta.',
                ]);
            }

            $customer = Customer::query()
                ->whereKey($lockedOrder->customer_id)
                ->lockForUpdate()
                ->first();

            if (! $customer?->is_active) {
                throw ValidationException::withMessages([
                    'order' => 'No se puede vender un pedido de un cliente inactivo.',
                ]);
            }

            $details = OrderDetail::query()
                ->where('order_id', $lockedOrder->getKey())
                ->with('product')
                ->orderBy('product_id')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($details->isEmpty()) {
                throw ValidationException::withMessages([
                    'order' => 'No se puede vender un pedido sin productos.',
                ]);
            }

            $requiredStock = [];
            foreach ($details as $detail) {
                $requiredStock[$detail->product_id] = ($requiredStock[$detail->product_id] ?? 0) + $detail->quantity;

                if ($requiredStock[$detail->product_id] > Inventory::MAX_STOCK) {
                    throw ValidationException::withMessages([
                        'order' => "La cantidad solicitada del producto {$detail->product->sku} excede el máximo permitido.",
                    ]);
                }
            }

            $inventories = Inventory::query()
                ->whereIn('product_id', array_keys($requiredStock))
                ->orderBy('product_id')
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('product_id');

            foreach ($requiredStock as $productId => $quantity) {
                $inventory = $inventories->get($productId);

                if (! $inventory instanceof Inventory) {
                    $product = $details->firstWhere('product_id', $productId)->product;
                    throw ValidationException::withMessages([
                        'order' => "El producto {$product->sku} no tiene inventario inicializado.",
                    ]);
                }

                if ($inventory->stock < $quantity) {
                    $product = $details->firstWhere('product_id', $productId)->product;
                    throw ValidationException::withMessages([
                        'order' => "Existencia insuficiente para el producto {$product->sku}.",
                    ]);
                }
            }

            ['details' => $saleDetails, 'total' => $saleTotal] = $this->calculateSaleDetails($details);

            $sale = Sale::create([
                'order_id' => $lockedOrder->getKey(),
                'customer_id' => $customer->getKey(),
                'number' => 'VEN-'.Str::upper((string) Str::ulid()),
                'sale_date' => today()->toDateString(),
                'total' => $saleTotal,
                'status' => SaleStatus::Confirmed,
                'notes' => $lockedOrder->notes,
            ]);

            $sale->details()->createMany($saleDetails);

            foreach ($details as $detail) {
                $inventory = $inventories->get($detail->product_id);
                $inventory->recordMovement(
                    InventoryMovementType::Exit,
                    $detail->quantity,
                    "Venta {$sale->number}",
                    $user,
                );
            }

            $lockedOrder->update([
                'status' => OrderStatus::Completed,
                'logistics_status' => OrderStatus::Confirmed,
            ]);
            $lockedOrder->recordStatusHistory(OrderStatus::Completed, $user);

            return $sale->load(['customer', 'order', 'details.product']);
        }, attempts: 3);
    }

    /**
     * @param  Collection<int, OrderDetail>  $details
     * @return array{details: array<int, array{product_id: int, quantity: int, unit_price: string, subtotal: string}>, total: string}
     */
    private function calculateSaleDetails(Collection $details): array
    {
        $saleDetails = [];
        $totalCents = 0;

        foreach ($details as $index => $detail) {
            $unitPriceCents = $this->toCents($detail->unit_price);

            if (
                $detail->quantity < 1
                || $unitPriceCents < 0
                || $unitPriceCents > intdiv(Order::MAX_TOTAL_CENTS, $detail->quantity)
            ) {
                throw ValidationException::withMessages([
                    "order.details.$index" => 'El detalle del pedido no tiene una cantidad o precio válido.',
                ]);
            }

            $subtotalCents = $detail->quantity * $unitPriceCents;

            if ($subtotalCents > Order::MAX_TOTAL_CENTS - $totalCents) {
                throw ValidationException::withMessages([
                    'order' => 'El total de la venta excede el valor máximo permitido.',
                ]);
            }

            $totalCents += $subtotalCents;
            $saleDetails[] = [
                'product_id' => $detail->product_id,
                'quantity' => $detail->quantity,
                'unit_price' => $this->formatCents($unitPriceCents),
                'subtotal' => $this->formatCents($subtotalCents),
            ];
        }

        return [
            'details' => $saleDetails,
            'total' => $this->formatCents($totalCents),
        ];
    }

    private function toCents(int|float|string $amount): int
    {
        $normalizedAmount = number_format((float) $amount, 2, '.', '');
        [$wholeUnits, $fractionalUnits] = explode('.', $normalizedAmount);

        return ((int) $wholeUnits * 100) + (int) $fractionalUnits;
    }

    private function formatCents(int $amount): string
    {
        return intdiv($amount, 100).'.'.str_pad((string) ($amount % 100), 2, '0', STR_PAD_LEFT);
    }
}
