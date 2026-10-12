<?php

namespace App\Actions;

use App\Enums\DentalInvoiceStatus;
use App\Enums\DentalPaymentStatus;
use App\Enums\PaymentMethod;
use App\Models\DentalInvoice;
use App\Models\DentalPayment;
use App\Models\User;
use App\Support\DentalDocumentNumber;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegisterDentalPayment
{
    /** @param array{amount: string, method: string, payment_date: string, idempotency_key: string, reference?: ?string, notes?: ?string} $data */
    public function handle(DentalInvoice $invoice, array $data, User $actor): DentalPayment
    {
        return DB::transaction(function () use ($invoice, $data, $actor): DentalPayment {
            $invoice = DentalInvoice::query()->whereKey($invoice->getKey())->lockForUpdate()->firstOrFail();

            $existingPayment = DentalPayment::query()
                ->where('idempotency_key', $data['idempotency_key'])
                ->first();

            if ($existingPayment !== null) {
                if ($existingPayment->dental_invoice_id !== $invoice->getKey()) {
                    throw ValidationException::withMessages([
                        'idempotency_key' => 'La solicitud de pago ya se utilizó en otra factura.',
                    ]);
                }

                return $existingPayment;
            }

            if (! in_array($invoice->status, [DentalInvoiceStatus::Issued, DentalInvoiceStatus::PartiallyPaid], true)) {
                throw ValidationException::withMessages([
                    'invoice' => 'Solo se aceptan pagos de facturas emitidas con saldo pendiente.',
                ]);
            }

            $amountCents = Money::toCents($data['amount']);
            $balanceCents = Money::toCents($invoice->balance_due);

            if ($amountCents <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'El monto debe ser mayor que cero.',
                ]);
            }

            if ($amountCents > $balanceCents) {
                throw ValidationException::withMessages([
                    'amount' => 'El pago no puede superar el saldo pendiente.',
                ]);
            }

            $method = PaymentMethod::from($data['method']);

            $payment = $invoice->payments()->create([
                'number' => DentalDocumentNumber::payment(),
                'idempotency_key' => $data['idempotency_key'],
                'payment_date' => $data['payment_date'],
                'amount' => Money::fromCents($amountCents),
                'method' => $method,
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => DentalPaymentStatus::Registered,
                'recorded_by' => $actor->getKey(),
            ]);

            $balanceCents -= $amountCents;
            $invoice->update([
                'balance_due' => Money::fromCents($balanceCents),
                'status' => $balanceCents === 0
                    ? DentalInvoiceStatus::Paid
                    : DentalInvoiceStatus::PartiallyPaid,
            ]);

            return $payment->load('invoice.patient');
        }, attempts: 3);
    }
}
