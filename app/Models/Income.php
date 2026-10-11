<?php

namespace App\Models;

use App\Enums\FinancialCategory;
use Database\Factories\IncomeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['payment_id', 'category', 'date', 'amount', 'description'])]
class Income extends Model
{
    /** @use HasFactory<IncomeFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'category' => FinancialCategory::class,
            'date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
