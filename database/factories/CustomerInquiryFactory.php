<?php

namespace Database\Factories;

use App\Enums\CustomerInquiryStatus;
use App\Models\Customer;
use App\Models\CustomerInquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerInquiry>
 */
class CustomerInquiryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => fake()->boolean(30) ? Customer::factory() : null,
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'subject' => fake()->randomElement(['Productos', 'Pedido', 'Cotización', 'Otro']),
            'message' => fake()->paragraph(),
            'status' => CustomerInquiryStatus::Pending,
        ];
    }
}
