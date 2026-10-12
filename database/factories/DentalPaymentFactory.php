<?php

namespace Database\Factories;

use App\Enums\DentalPaymentStatus;
use App\Enums\PaymentMethod;
use App\Models\DentalInvoice;
use App\Models\DentalPayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DentalPayment>
 */
class DentalPaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dental_invoice_id' => DentalInvoice::factory()->issued(),
            'number' => 'DREC-'.Str::upper((string) Str::ulid()),
            'idempotency_key' => (string) Str::uuid(),
            'payment_date' => today(),
            'amount' => '25.00',
            'method' => PaymentMethod::Cash,
            'reference' => null,
            'notes' => null,
            'status' => DentalPaymentStatus::Registered,
            'recorded_by' => User::factory(),
        ];
    }
}
