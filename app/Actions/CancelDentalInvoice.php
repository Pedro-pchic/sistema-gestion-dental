<?php

namespace App\Actions;

use App\Enums\DentalInvoiceStatus;
use App\Enums\DentalPaymentStatus;
use App\Models\DentalInvoice;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelDentalInvoice
{
    public function handle(DentalInvoice $invoice, User $actor, string $reason): DentalInvoice
    {
        return DB::transaction(function () use ($invoice, $actor, $reason): DentalInvoice {
            $invoice = DentalInvoice::query()->whereKey($invoice->getKey())->lockForUpdate()->firstOrFail();

            if ($invoice->status === DentalInvoiceStatus::Cancelled) {
                throw ValidationException::withMessages([
                    'invoice' => 'La factura ya está anulada.',
                ]);
            }

            if ($invoice->payments()->where('status', DentalPaymentStatus::Registered->value)->exists()) {
                throw ValidationException::withMessages([
                    'invoice' => 'Anula primero los pagos registrados antes de anular la factura.',
                ]);
            }

            $invoice->update([
                'status' => DentalInvoiceStatus::Cancelled,
                'balance_due' => '0.00',
                'cancelled_by' => $actor->getKey(),
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            return $invoice->fresh();
        }, attempts: 3);
    }
}
