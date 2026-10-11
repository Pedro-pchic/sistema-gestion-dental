<?php

namespace Database\Factories;

use App\Enums\PurchaseStatus;
use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Purchase>
 */
class PurchaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'number' => 'OC-'.Str::upper((string) Str::ulid()),
            'status' => PurchaseStatus::Draft,
            'order_date' => fake()->date(),
            'received_at' => null,
            'notes' => fake()->optional()->sentence(),
            'total' => '0.00',
        ];
    }
}
