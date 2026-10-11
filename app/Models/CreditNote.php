<?php

namespace App\Models;

use App\Enums\CreditNoteStatus;
use Database\Factories\CreditNoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['invoice_id', 'number', 'issue_date', 'amount', 'reason', 'status'])]
class CreditNote extends Model
{
    /** @use HasFactory<CreditNoteFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'amount' => 'decimal:2',
            'status' => CreditNoteStatus::class,
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
