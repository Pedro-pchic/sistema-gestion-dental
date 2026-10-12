<?php

namespace Database\Factories;

use App\Enums\TreatmentPlanStatus;
use App\Models\Dentist;
use App\Models\Patient;
use App\Models\TreatmentPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TreatmentPlan>
 */
class TreatmentPlanFactory extends Factory
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
            'created_by' => User::factory(),
            'updated_by' => null,
            'title' => fake()->sentence(4),
            'description' => fake()->sentence(),
            'status' => TreatmentPlanStatus::Pending,
        ];
    }
}
