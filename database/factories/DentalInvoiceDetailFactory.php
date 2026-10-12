<?php

namespace Database\Factories;

use App\Models\DentalInvoice;
use App\Models\DentalInvoiceDetail;
use App\Models\PatientTreatment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DentalInvoiceDetail>
 */
class DentalInvoiceDetailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dental_invoice_id' => DentalInvoice::factory(),
            'patient_treatment_id' => PatientTreatment::factory(),
            'description' => 'Consulta odontológica',
            'quantity' => 1,
            'unit_price' => '100.00',
            'subtotal' => '100.00',
        ];
    }
}
