<?php

namespace App\Actions;

use App\Enums\DentalInvoiceStatus;
use App\Models\DentalInvoice;
use App\Models\DentalInvoiceDetail;
use App\Models\Patient;
use App\Models\PatientTreatment;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateDentalInvoiceDraft
{
    /** @param list<int|string> $patientTreatmentIds */
    public function handle(Patient $patient, array $patientTreatmentIds, User $actor): DentalInvoice
    {
        $patientTreatmentIds = array_values(array_unique(array_map('intval', $patientTreatmentIds)));

        return DB::transaction(function () use ($patient, $patientTreatmentIds, $actor): DentalInvoice {
            $procedures = PatientTreatment::query()
                ->where('patient_id', $patient->getKey())
                ->whereIn('id', $patientTreatmentIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->with('treatment:id,name')
                ->get();

            if ($procedures->count() !== count($patientTreatmentIds)) {
                throw ValidationException::withMessages([
                    'patient_treatment_ids' => 'Selecciona procedimientos realizados que pertenezcan al paciente.',
                ]);
            }

            $alreadyReserved = DentalInvoiceDetail::query()
                ->whereIn('patient_treatment_id', $patientTreatmentIds)
                ->whereHas('invoice', fn ($query) => $query->where('status', '!=', DentalInvoiceStatus::Cancelled->value))
                ->exists();

            if ($alreadyReserved) {
                throw ValidationException::withMessages([
                    'patient_treatment_ids' => 'Uno o más procedimientos ya están incluidos en otra factura activa.',
                ]);
            }

            $invoice = DentalInvoice::query()->create([
                'patient_id' => $patient->getKey(),
                'status' => DentalInvoiceStatus::Draft,
                'subtotal' => '0.00',
                'discount' => '0.00',
                'tax_amount' => '0.00',
                'total' => '0.00',
                'balance_due' => '0.00',
                'created_by' => $actor->getKey(),
            ]);

            $subtotalCents = 0;

            foreach ($procedures as $procedure) {
                $unitPriceCents = Money::toCents($procedure->price_applied);

                if ($unitPriceCents <= 0) {
                    throw ValidationException::withMessages([
                        'patient_treatment_ids' => 'No se pueden facturar procedimientos con precio cero o negativo.',
                    ]);
                }

                $lineSubtotalCents = $unitPriceCents;
                $subtotalCents += $lineSubtotalCents;

                $invoice->details()->create([
                    'patient_treatment_id' => $procedure->getKey(),
                    'description' => $procedure->treatment->name,
                    'quantity' => 1,
                    'unit_price' => Money::fromCents($unitPriceCents),
                    'subtotal' => Money::fromCents($lineSubtotalCents),
                ]);
            }

            $invoice->update([
                'subtotal' => Money::fromCents($subtotalCents),
                'total' => Money::fromCents($subtotalCents),
            ]);

            return $invoice->load('details');
        }, attempts: 3);
    }
}
