<?php

namespace App\Actions;

use App\Enums\CreditNoteStatus;
use App\Models\CreditNote;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelCreditNote
{
    public function handle(CreditNote $creditNote): CreditNote
    {
        return DB::transaction(function () use ($creditNote): CreditNote {
            $invoice = Invoice::query()
                ->whereKey($creditNote->invoice_id)
                ->lockForUpdate()
                ->firstOrFail();
            $creditNote = CreditNote::query()
                ->whereKey($creditNote->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($creditNote->status === CreditNoteStatus::Cancelled) {
                throw ValidationException::withMessages([
                    'credit_note' => 'La nota de crédito ya está cancelada.',
                ]);
            }

            $creditNote->update(['status' => CreditNoteStatus::Cancelled]);
            $invoice->synchronizeStatus();

            return $creditNote;
        }, attempts: 3);
    }
}
