<?php

namespace Database\Factories;

use App\Enums\DentalInvoiceStatus;
use App\Models\DentalInvoice;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DentalInvoice>
 */
class DentalInvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'number' => null,
            'status' => DentalInvoiceStatus::Draft,
            'issue_date' => null,
            'subtotal' => '0.00',
            'discount' => '0.00',
            'tax_amount' => '0.00',
            'total' => '0.00',
            'balance_due' => '0.00',
            'created_by' => User::factory(),
            'issued_by' => null,
            'cancelled_by' => null,
        ];
    }

    public function issued(): static
    {
        return $this->state(fn (array $attributes): array => [
            'number' => 'DFA-'.Str::upper((string) Str::ulid()),
            'status' => DentalInvoiceStatus::Issued,
            'issue_date' => today(),
            'subtotal' => '100.00',
            'total' => '100.00',
            'balance_due' => '100.00',
            'issued_by' => $attributes['created_by'],
        ]);
    }
}
