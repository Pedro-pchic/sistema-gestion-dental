<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuoteDetail>
 */
class QuoteDetailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quote_id' => Quote::factory(),
            'product_id' => Product::factory(),
            'quantity' => 1,
            'unit_price' => '1.00',
            'subtotal' => '1.00',
        ];
    }
}
