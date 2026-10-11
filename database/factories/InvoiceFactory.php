<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sale_id' => Sale::factory()->state(['total' => '1.00']),
            'number' => 'FAC-'.Str::upper((string) Str::ulid()),
            'issue_date' => today(),
            'status' => InvoiceStatus::Issued,
            'subtotal' => '1.00',
            'total' => '1.00',
            'notes' => null,
        ];
    }
}
