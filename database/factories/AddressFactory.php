<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'label' => fake()->optional()->randomElement(['Casa', 'Trabajo', 'Sucursal']),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'department' => fake()->state(),
            'is_default' => false,
        ];
    }
}
