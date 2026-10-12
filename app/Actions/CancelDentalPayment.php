<?php

namespace App\Actions;

use App\Enums\DentalInvoiceStatus;
use App\Enums\DentalPaymentStatus;
use App\Models\DentalInvoice;
use App\Models\DentalPayment;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelDentalPayment
{
    public function handle(DentalPayment $payment, User $actor, string $reason): DentalPayment
    {
        return DB::transaction(function () use ($payment, $actor, $reason): DentalPayment {
            $invoice = DentalInvoice::query()
                ->whereKey($payment->dental_invoice_id)
                ->lockForUpdate()
                ->firstOrFail();
            $payment = DentalPayment::query()->whereKey($payment->getKey())->lockForUpdate()->firstOrFail();

            if ($payment->status !== DentalPaymentStatus::Registered) {
                throw ValidationException::withMessages([
                    'payment' => 'Solo se pueden anular pagos registrados.',
                ]);
            }

            if ($invoice->status === DentalInvoiceStatus::Cancelled) {
                throw ValidationException::withMessages([
                    'payment' => 'No se pueden corregir pagos de una factura anulada.',
                ]);
            }

            $balanceCents = Money::toCents($invoice->balance_due) + Money::toCents($payment->amount);
            $totalCents = Money::toCents($invoice->total);

            if ($balanceCents > $totalCents) {
                throw ValidationException::withMessages([
                    'payment' => 'La corrección excede el total de la factura.',
                ]);
            }

            $payment->update([
                'status' => DentalPaymentStatus::Cancelled,
                'cancelled_by' => $actor->getKey(),
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            $hasRegisteredPayments = $invoice->payments()
                ->where('status', DentalPaymentStatus::Registered->value)
                ->exists();

            $invoice->update([
                'balance_due' => Money::fromCents($balanceCents),
                'status' => $balanceCents === $totalCents
                    ? DentalInvoiceStatus::Issued
                    : ($hasRegisteredPayments ? DentalInvoiceStatus::PartiallyPaid : DentalInvoiceStatus::Issued),
            ]);

            return $payment->fresh(['invoice.patient']);
        }, attempts: 3);
    }
}
