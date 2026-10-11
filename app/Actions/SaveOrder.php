<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Enums\QuoteStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Quote;
use App\Models\QuoteDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SaveOrder
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data, ?Order $order = null, ?Quote $sourceQuote = null): Order
    {
        return DB::transaction(function () use ($data, $order, $sourceQuote): Order {
            $lockedOrder = null;

            if ($order !== null) {
                $lockedOrder = Order::query()
                    ->whereKey($order->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $lockedOrder->isEditable()) {
                    throw ValidationException::withMessages([
                        'order' => 'Solo se pueden editar pedidos pendientes.',
                    ]);
                }
            }

            $quote = null;

            if ($sourceQuote !== null) {
                if ($lockedOrder !== null) {
                    throw ValidationException::withMessages([
                        'quote' => 'La cotización solo puede convertirse al crear un pedido.',
                    ]);
                }

                $quote = Quote::query()
                    ->whereKey($sourceQuote->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($quote->status !== QuoteStatus::Accepted || $quote->order()->exists()) {
                    throw ValidationException::withMessages([
                        'quote' => 'Solo se puede convertir una cotización aceptada que aún no tenga pedido.',
                    ]);
                }

                $submittedDetails = $quote->details()
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->map(fn (QuoteDetail $detail): array => [
                        'product_id' => $detail->product_id,
                        'quantity' => $detail->quantity,
                        'unit_price' => $detail->unit_price,
                    ])
                    ->all();
            } else {
                $submittedDetails = $data['details'];
            }

            $customerId = $quote?->customer_id ?? (int) $data['customer_id'];
            $customer = Customer::query()
                ->whereKey($customerId)
                ->lockForUpdate()
                ->first();

            if (! $customer?->is_active) {
                throw ValidationException::withMessages([
                    'customer_id' => 'El pedido debe pertenecer a un cliente activo.',
                ]);
            }

            ['details' => $details, 'total' => $total] = $this->calculateDetails($submittedDetails);

            $attributes = [
                'customer_id' => $customer->id,
                'order_date' => $data['order_date'] ?? today()->toDateString(),
                'notes' => array_key_exists('notes', $data) ? $data['notes'] : $quote?->notes,
                'total' => $total,
            ];

            if ($lockedOrder === null) {
                $attributes['quote_id'] = $quote?->id;
                $attributes['number'] = 'PED-'.Str::upper((string) Str::ulid());
                $attributes['status'] = OrderStatus::Pending;
                $lockedOrder = Order::create($attributes);
                $lockedOrder->recordStatusHistory(OrderStatus::Pending);
            } else {
                $lockedOrder->update($attributes);
                $lockedOrder->details()->delete();
            }

            $lockedOrder->details()->createMany($details);

            return $lockedOrder;
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $submittedDetails
     * @return array{details: array<int, array{product_id: int, quantity: int, unit_price: string, subtotal: string}>, total: string}
     */
    private function calculateDetails(array $submittedDetails): array
    {
        if ($submittedDetails === []) {
            throw ValidationException::withMessages([
                'details' => 'Agrega al menos un producto al pedido.',
            ]);
        }

        $details = [];
        $totalCents = 0;

        foreach ($submittedDetails as $index => $submittedDetail) {
            $quantity = (int) $submittedDetail['quantity'];
            $unitPriceCents = $this->toCents($submittedDetail['unit_price']);

            if ($quantity < 1 || $unitPriceCents < 0) {
                throw ValidationException::withMessages([
                    "details.$index" => 'Cada detalle debe tener cantidad positiva y precio no negativo.',
                ]);
            }

            if ($unitPriceCents > intdiv(Order::MAX_TOTAL_CENTS, $quantity)) {
                throw ValidationException::withMessages([
                    "details.$index.unit_price" => 'El subtotal del detalle excede el valor máximo permitido.',
                ]);
            }

            $subtotalCents = $quantity * $unitPriceCents;

            if ($subtotalCents > Order::MAX_TOTAL_CENTS - $totalCents) {
                throw ValidationException::withMessages([
                    "details.$index.unit_price" => 'El total del pedido excede el valor máximo permitido.',
                ]);
            }

            $totalCents += $subtotalCents;
            $details[] = [
                'product_id' => (int) $submittedDetail['product_id'],
                'quantity' => $quantity,
                'unit_price' => $this->formatCents($unitPriceCents),
                'subtotal' => $this->formatCents($subtotalCents),
            ];
        }

        return [
            'details' => $details,
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
