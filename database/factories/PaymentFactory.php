<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'number' => 'REC-'.Str::upper((string) Str::ulid()),
            'payment_date' => today(),
            'amount' => '0.50',
            'method' => PaymentMethod::Cash,
            'notes' => null,
        ];
    }
}
