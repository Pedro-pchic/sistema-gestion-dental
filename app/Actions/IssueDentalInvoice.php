<?php

namespace App\Actions;

use App\Enums\DentalInvoiceStatus;
use App\Models\DentalInvoice;
use App\Models\User;
use App\Support\DentalDocumentNumber;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IssueDentalInvoice
{
    public function handle(DentalInvoice $invoice, User $actor): DentalInvoice
    {
        return DB::transaction(function () use ($invoice, $actor): DentalInvoice {
            $invoice = DentalInvoice::query()->whereKey($invoice->getKey())->lockForUpdate()->firstOrFail();

            if ($invoice->status !== DentalInvoiceStatus::Draft) {
                throw ValidationException::withMessages([
                    'invoice' => 'Solo se pueden emitir facturas en borrador.',
                ]);
            }

            $details = $invoice->details()->lockForUpdate()->get();

            if ($details->isEmpty()) {
                throw ValidationException::withMessages([
                    'invoice' => 'No se puede emitir una factura sin conceptos.',
                ]);
            }

            $subtotalCents = 0;

            foreach ($details as $detail) {
                $unitPriceCents = Money::toCents($detail->unit_price);
                $lineTotalCents = $unitPriceCents * $detail->quantity;

                if ($unitPriceCents <= 0 || $detail->quantity < 1 || $lineTotalCents !== Money::toCents($detail->subtotal)) {
                    throw ValidationException::withMessages([
                        'invoice' => 'La factura contiene conceptos con importes inconsistentes.',
                    ]);
                }

                $subtotalCents += $lineTotalCents;
            }

            $discountCents = Money::toCents($invoice->discount);
            $taxCents = Money::toCents($invoice->tax_amount);
            $totalCents = $subtotalCents - $discountCents + $taxCents;

            if ($subtotalCents <= 0 || $discountCents < 0 || $discountCents > $subtotalCents || $taxCents < 0 || $totalCents <= 0) {
                throw ValidationException::withMessages([
                    'invoice' => 'Los importes de la factura deben ser positivos y consistentes.',
                ]);
            }

            $invoice->update([
                'number' => DentalDocumentNumber::invoice(),
                'status' => DentalInvoiceStatus::Issued,
                'issue_date' => today(),
                'subtotal' => Money::fromCents($subtotalCents),
                'total' => Money::fromCents($totalCents),
                'balance_due' => Money::fromCents($totalCents),
                'issued_by' => $actor->getKey(),
            ]);

            return $invoice->fresh(['patient', 'details']);
        }, attempts: 3);
    }
}
