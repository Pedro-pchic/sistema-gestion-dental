<?php

namespace App\Actions;

use App\Enums\PurchaseStatus;
use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SavePurchaseDraft
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data, ?Purchase $purchase = null): Purchase
    {
        return DB::transaction(function () use ($data, $purchase): Purchase {
            if ($purchase !== null) {
                $purchase = Purchase::query()
                    ->whereKey($purchase->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $purchase->isEditable()) {
                    throw ValidationException::withMessages([
                        'purchase' => 'Solo se pueden editar órdenes en borrador.',
                    ]);
                }
            }

            $supplier = Supplier::query()
                ->whereKey($data['supplier_id'])
                ->lockForUpdate()
                ->first();

            if (! $supplier?->is_active) {
                throw ValidationException::withMessages([
                    'supplier_id' => 'La orden debe pertenecer a un proveedor activo.',
                ]);
            }

            ['details' => $details, 'total' => $total] = $this->calculateDetails($data['details']);

            if ($purchase === null) {
                $purchase = Purchase::create([
                    'supplier_id' => $supplier->id,
                    'number' => 'OC-'.Str::upper((string) Str::ulid()),
                    'status' => PurchaseStatus::Draft,
                    'order_date' => $data['order_date'],
                    'notes' => $data['notes'] ?? null,
                    'total' => $total,
                ]);
            } else {
                $purchase->update([
                    'supplier_id' => $supplier->id,
                    'order_date' => $data['order_date'],
                    'notes' => $data['notes'] ?? null,
                    'total' => $total,
                ]);
                $purchase->details()->delete();
            }

            $purchase->details()->createMany($details);

            return $purchase;
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $submittedDetails
     * @return array{details: array<int, array{product_id: int, quantity: int, unit_cost: string, subtotal: string}>, total: string}
     */
    private function calculateDetails(array $submittedDetails): array
    {
        $details = [];
        $totalCents = 0;

        foreach ($submittedDetails as $index => $submittedDetail) {
            $quantity = (int) $submittedDetail['quantity'];
            $unitCostCents = $this->toCents($submittedDetail['unit_cost']);

            if ($quantity < 1 || $unitCostCents < 0) {
                throw ValidationException::withMessages([
                    "details.$index" => 'Cada detalle debe tener cantidad positiva y costo no negativo.',
                ]);
            }

            if ($unitCostCents > intdiv(Purchase::MAX_TOTAL_CENTS, $quantity)) {
                throw ValidationException::withMessages([
                    "details.$index.unit_cost" => 'El subtotal del detalle excede el valor máximo permitido.',
                ]);
            }

            $subtotalCents = $quantity * $unitCostCents;

            if ($subtotalCents > Purchase::MAX_TOTAL_CENTS - $totalCents) {
                throw ValidationException::withMessages([
                    "details.$index.unit_cost" => 'El total de la orden excede el valor máximo permitido.',
                ]);
            }

            $totalCents += $subtotalCents;
            $details[] = [
                'product_id' => (int) $submittedDetail['product_id'],
                'quantity' => $quantity,
                'unit_cost' => $this->formatCents($unitCostCents),
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
