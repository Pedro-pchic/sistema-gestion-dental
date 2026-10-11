<?php

namespace Database\Factories;

use App\Enums\QuoteStatus;
use App\Models\Customer;
use App\Models\Quote;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Quote>
 */
class QuoteFactory extends Factory
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
            'number' => 'COT-'.Str::upper((string) Str::ulid()),
            'status' => QuoteStatus::Draft,
            'quote_date' => today(),
            'valid_until' => today()->addDays(15),
            'notes' => null,
            'total' => '0.00',
        ];
    }
}
