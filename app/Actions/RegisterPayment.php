<?php

namespace App\Actions;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\Payment;
use App\Support\CommercialDocumentNumber;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegisterPayment
{
    public function __construct(private RecordPaymentIncome $recordPaymentIncome) {}

    public function handle(
        Invoice $invoice,
        string $amount,
        PaymentMethod $method,
        string $paymentDate,
        ?string $notes,
    ): Payment {
        return DB::transaction(function () use ($invoice, $amount, $method, $paymentDate, $notes): Payment {
            $invoice = Invoice::query()->whereKey($invoice->getKey())->lockForUpdate()->firstOrFail();

            if ($invoice->status === InvoiceStatus::Cancelled) {
                throw ValidationException::withMessages([
                    'invoice' => 'No se pueden registrar pagos en una factura cancelada.',
                ]);
            }

            $amountCents = Money::toCents($amount);

            if ($amountCents <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'El monto del pago debe ser mayor que cero.',
                ]);
            }

            if ($amountCents > $invoice->availableAmountCents()) {
                throw ValidationException::withMessages([
                    'amount' => 'El pago supera el saldo disponible de la factura.',
                ]);
            }

            $payment = $invoice->payments()->create([
                'number' => CommercialDocumentNumber::payment(),
                'payment_date' => $paymentDate,
                'amount' => Money::fromCents($amountCents),
                'method' => $method,
                'notes' => $notes,
            ]);

            $this->recordPaymentIncome->handle($payment);

            $invoice->synchronizeStatus();

            return $payment;
        }, attempts: 3);
    }
}
