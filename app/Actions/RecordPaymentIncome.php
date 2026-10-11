<?php

namespace App\Actions;

use App\Enums\FinancialCategory;
use App\Models\Income;
use App\Models\Payment;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordPaymentIncome
{
    public function handle(Payment $payment): Income
    {
        return DB::transaction(function () use ($payment): Income {
            $payment = Payment::query()
                ->whereKey($payment->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $existingIncome = $payment->income()->first();

            if ($existingIncome instanceof Income) {
                return $existingIncome;
            }

            $amountCents = Money::toCents($payment->amount);

            if ($amountCents <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'El ingreso derivado requiere un pago mayor que cero.',
                ]);
            }

            return $payment->income()->create([
                'category' => FinancialCategory::Sales,
                'date' => $payment->payment_date,
                'amount' => Money::fromCents($amountCents),
                'description' => "Pago {$payment->number}",
            ]);
        });
    }
}
