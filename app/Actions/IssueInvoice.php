<?php

namespace App\Actions;

use App\Enums\InvoiceStatus;
use App\Enums\SaleStatus;
use App\Models\Invoice;
use App\Models\Sale;
use App\Support\CommercialDocumentNumber;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IssueInvoice
{
    public function handle(Sale $sale): Invoice
    {
        return DB::transaction(function () use ($sale): Invoice {
            $sale = Sale::query()->whereKey($sale->getKey())->lockForUpdate()->firstOrFail();

            if ($sale->status !== SaleStatus::Confirmed) {
                throw ValidationException::withMessages([
                    'sale' => 'Solo se pueden facturar ventas confirmadas.',
                ]);
            }

            if ($sale->invoice()->exists()) {
                throw ValidationException::withMessages([
                    'sale' => 'Esta venta ya tiene una factura.',
                ]);
            }

            $details = $sale->details()->get();

            if ($details->isEmpty()) {
                throw ValidationException::withMessages([
                    'sale' => 'La venta debe tener al menos una línea para poder facturarse.',
                ]);
            }

            $subtotalCents = 0;

            foreach ($details as $detail) {
                $lineTotalCents = Money::toCents($detail->unit_price) * $detail->quantity;

                if ($lineTotalCents !== Money::toCents($detail->subtotal)) {
                    throw ValidationException::withMessages([
                        'sale' => 'Las líneas de la venta tienen importes inconsistentes y no se pueden facturar.',
                    ]);
                }

                $subtotalCents += $lineTotalCents;
            }

            if ($subtotalCents <= 0 || $subtotalCents !== Money::toCents($sale->total)) {
                throw ValidationException::withMessages([
                    'sale' => 'El total de la venta no coincide con sus líneas y no se puede facturar.',
                ]);
            }

            $total = Money::fromCents($subtotalCents);

            return $sale->invoice()->create([
                'number' => CommercialDocumentNumber::invoice(),
                'issue_date' => today(),
                'status' => InvoiceStatus::Issued,
                'subtotal' => $total,
                'total' => $total,
                'notes' => $sale->notes,
            ]);
        }, attempts: 3);
    }
}
