<?php

namespace App\Actions;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelInvoice
{
    public function handle(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice): Invoice {
            $invoice = Invoice::query()->whereKey($invoice->getKey())->lockForUpdate()->firstOrFail();

            if ($invoice->status === InvoiceStatus::Cancelled) {
                throw ValidationException::withMessages([
                    'invoice' => 'La factura ya está cancelada.',
                ]);
            }

            if ($invoice->payments()->exists() || $invoice->creditNotes()->where('status', 'issued')->exists()) {
                throw ValidationException::withMessages([
                    'invoice' => 'No se puede cancelar una factura que ya tiene pagos o notas de crédito vigentes.',
                ]);
            }

            $invoice->update(['status' => InvoiceStatus::Cancelled]);

            return $invoice;
        }, attempts: 3);
    }
}
