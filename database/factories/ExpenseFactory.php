<?php

namespace Database\Factories;

use App\Enums\FinancialCategory;
use App\Models\Expense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'purchase_id' => null,
            'category' => FinancialCategory::Operating,
            'date' => today(),
            'amount' => '1.00',
            'description' => fake()->sentence(),
        ];
    }
}
