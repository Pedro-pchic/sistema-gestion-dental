<?php

namespace Database\Factories;

use App\Enums\CreditNoteStatus;
use App\Models\CreditNote;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CreditNote>
 */
class CreditNoteFactory extends Factory
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
            'number' => 'NDC-'.Str::upper((string) Str::ulid()),
            'issue_date' => today(),
            'amount' => '0.50',
            'reason' => fake()->sentence(),
            'status' => CreditNoteStatus::Issued,
        ];
    }
}
