<?php

namespace Database\Factories;

use App\Enums\TreatmentPlanStatus;
use App\Models\Treatment;
use App\Models\TreatmentPlan;
use App\Models\TreatmentPlanItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TreatmentPlanItem>
 */
class TreatmentPlanItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'treatment_plan_id' => TreatmentPlan::factory(),
            'treatment_id' => Treatment::factory(),
            'quantity' => 1,
            'estimated_unit_price' => 250,
            'status' => TreatmentPlanStatus::Pending,
            'sort_order' => 0,
        ];
    }
}
