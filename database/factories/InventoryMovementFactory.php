<?php

namespace Database\Factories;

use App\Enums\InventoryMovementType;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryMovement>
 */
class InventoryMovementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'inventory_id' => Inventory::factory(),
            'user_id' => null,
            'type' => InventoryMovementType::Entry,
            'quantity' => 1,
            'previous_stock' => 0,
            'resulting_stock' => 1,
            'reason' => null,
        ];
    }
}
