<?php

namespace App\Actions;

use App\Enums\FinancialCategory;
use App\Enums\PurchaseStatus;
use App\Models\Expense;
use App\Models\Purchase;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordExpense
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Expense
    {
        return DB::transaction(function () use ($data): Expense {
            $purchaseId = isset($data['purchase_id']) ? (int) $data['purchase_id'] : null;

            if ($purchaseId !== null) {
                $purchase = Purchase::query()
                    ->whereKey($purchaseId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($purchase->status !== PurchaseStatus::Received) {
                    throw ValidationException::withMessages([
                        'purchase_id' => 'Solo una compra recibida puede registrarse explícitamente como gasto.',
                    ]);
                }

                if ($purchase->expense()->exists()) {
                    throw ValidationException::withMessages([
                        'purchase_id' => 'Esta compra ya tiene un gasto relacionado.',
                    ]);
                }

                $amountCents = Money::toCents($purchase->total);
                $category = FinancialCategory::Purchases;
            } else {
                $amountCents = Money::toCents((string) $data['amount']);
                $category = FinancialCategory::from($data['category']);
            }

            if ($amountCents <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'El monto del gasto debe ser mayor que cero.',
                ]);
            }

            return Expense::create([
                'purchase_id' => $purchaseId,
                'category' => $category,
                'date' => $data['date'],
                'amount' => Money::fromCents($amountCents),
                'description' => $data['description'],
            ]);
        }, attempts: 3);
    }
}
