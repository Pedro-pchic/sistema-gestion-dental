<?php

namespace Database\Factories;

use App\Enums\FinancialCategory;
use App\Models\Income;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Income>
 */
class IncomeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_id' => null,
            'category' => FinancialCategory::OtherIncome,
            'date' => today(),
            'amount' => '1.00',
            'description' => fake()->sentence(),
        ];
    }
}
