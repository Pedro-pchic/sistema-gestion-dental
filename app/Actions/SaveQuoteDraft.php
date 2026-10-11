<?php

namespace App\Actions;

use App\Enums\QuoteStatus;
use App\Models\Customer;
use App\Models\Quote;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SaveQuoteDraft
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data, ?Quote $quote = null): Quote
    {
        return DB::transaction(function () use ($data, $quote): Quote {
            if ($quote !== null) {
                $quote = Quote::query()
                    ->whereKey($quote->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $quote->isEditable()) {
                    throw ValidationException::withMessages([
                        'quote' => 'Solo se pueden editar cotizaciones en borrador.',
                    ]);
                }
            }

            $customer = Customer::query()
                ->whereKey($data['customer_id'])
                ->lockForUpdate()
                ->first();

            if (! $customer?->is_active) {
                throw ValidationException::withMessages([
                    'customer_id' => 'La cotización debe pertenecer a un cliente activo.',
                ]);
            }

            ['details' => $details, 'total' => $total] = $this->calculateDetails($data['details']);

            if ($quote === null) {
                $quote = Quote::create([
                    'customer_id' => $customer->id,
                    'number' => 'COT-'.Str::upper((string) Str::ulid()),
                    'status' => QuoteStatus::Draft,
                    'quote_date' => $data['quote_date'],
                    'valid_until' => $data['valid_until'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'total' => $total,
                ]);
            } else {
                $quote->update([
                    'customer_id' => $customer->id,
                    'quote_date' => $data['quote_date'],
                    'valid_until' => $data['valid_until'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'total' => $total,
                ]);
                $quote->details()->delete();
            }

            $quote->details()->createMany($details);

            return $quote;
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
                'details' => 'Agrega al menos un producto a la cotización.',
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

            if ($unitPriceCents > intdiv(Quote::MAX_TOTAL_CENTS, $quantity)) {
                throw ValidationException::withMessages([
                    "details.$index.unit_price" => 'El subtotal del detalle excede el valor máximo permitido.',
                ]);
            }

            $subtotalCents = $quantity * $unitPriceCents;

            if ($subtotalCents > Quote::MAX_TOTAL_CENTS - $totalCents) {
                throw ValidationException::withMessages([
                    "details.$index.unit_price" => 'El total de la cotización excede el valor máximo permitido.',
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
