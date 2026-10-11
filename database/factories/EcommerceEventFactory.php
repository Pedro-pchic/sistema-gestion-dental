<?php

namespace Database\Factories;

use App\Enums\EcommerceEventType;
use App\Models\EcommerceEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EcommerceEvent>
 */
class EcommerceEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_type' => EcommerceEventType::PortalVisit,
            'product_id' => null,
            'order_id' => null,
            'session_identifier' => hash('sha256', fake()->uuid()),
            'occurred_at' => now(),
        ];
    }
}
