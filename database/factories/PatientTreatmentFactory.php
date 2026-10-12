<?php

namespace Database\Factories;

use App\Models\Dentist;
use App\Models\Patient;
use App\Models\PatientTreatment;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PatientTreatment>
 */
class PatientTreatmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'dentist_id' => Dentist::factory(),
            'treatment_id' => Treatment::factory(),
            'appointment_id' => null,
            'treatment_plan_item_id' => null,
            'registered_by' => User::factory(),
            'updated_by' => null,
            'performed_at' => now(),
            'procedure_description' => 'Procedimiento realizado.',
            'clinical_notes' => null,
            'price_applied' => 250,
        ];
    }
}
