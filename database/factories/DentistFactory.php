<?php

namespace Database\Factories;

use App\Models\Dentist;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dentist>
 */
class DentistFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'license_number' => fake()->unique()->bothify('OD-########'),
            'specialty' => fake()->optional()->jobTitle(),
            'is_active' => true,
        ];
    }
}
