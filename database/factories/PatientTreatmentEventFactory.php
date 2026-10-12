<?php

namespace Database\Factories;

use App\Models\PatientTreatment;
use App\Models\PatientTreatmentEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PatientTreatmentEvent>
 */
class PatientTreatmentEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_treatment_id' => PatientTreatment::factory(),
            'user_id' => User::factory(),
            'event_type' => 'updated',
            'changes' => ['clinical_notes' => ['old' => null, 'new' => 'Nota actualizada']],
        ];
    }
}
