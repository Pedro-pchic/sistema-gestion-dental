<?php

namespace Database\Factories;

use App\Enums\SaleStatus;
use App\Models\Customer;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => null,
            'customer_id' => Customer::factory(),
            'number' => 'VEN-'.Str::upper((string) Str::ulid()),
            'sale_date' => today(),
            'total' => '0.00',
            'status' => SaleStatus::Confirmed,
            'notes' => null,
        ];
    }
}
