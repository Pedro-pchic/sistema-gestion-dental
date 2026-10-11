<?php

namespace App\Actions;

use App\Enums\CreditNoteStatus;
use App\Enums\InvoiceStatus;
use App\Models\CreditNote;
use App\Models\Invoice;
use App\Support\CommercialDocumentNumber;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IssueCreditNote
{
    public function handle(Invoice $invoice, string $amount, string $reason, string $issueDate): CreditNote
    {
        return DB::transaction(function () use ($invoice, $amount, $reason, $issueDate): CreditNote {
            $invoice = Invoice::query()->whereKey($invoice->getKey())->lockForUpdate()->firstOrFail();

            if ($invoice->status === InvoiceStatus::Cancelled) {
                throw ValidationException::withMessages([
                    'invoice' => 'No se pueden emitir notas de crédito para una factura cancelada.',
                ]);
            }

            $amountCents = Money::toCents($amount);

            if ($amountCents <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'El monto de la nota de crédito debe ser mayor que cero.',
                ]);
            }

            if ($amountCents > $invoice->availableAmountCents()) {
                throw ValidationException::withMessages([
                    'amount' => 'La nota de crédito supera el importe acreditable disponible.',
                ]);
            }

            $creditNote = $invoice->creditNotes()->create([
                'number' => CommercialDocumentNumber::creditNote(),
                'issue_date' => $issueDate,
                'amount' => Money::fromCents($amountCents),
                'reason' => $reason,
                'status' => CreditNoteStatus::Issued,
            ]);

            $invoice->synchronizeStatus();

            return $creditNote;
        }, attempts: 3);
    }
}
