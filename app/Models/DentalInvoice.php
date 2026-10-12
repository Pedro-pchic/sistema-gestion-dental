<?php

namespace App\Models;

use App\Enums\DentalInvoiceStatus;
use Database\Factories\DentalInvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'patient_id',
    'number',
    'status',
    'issue_date',
    'subtotal',
    'discount',
    'tax_amount',
    'total',
    'balance_due',
    'created_by',
    'issued_by',
    'cancelled_by',
    'cancelled_at',
    'cancellation_reason',
])]
class DentalInvoice extends Model
{
    /** @use HasFactory<DentalInvoiceFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => DentalInvoiceStatus::class,
            'issue_date' => 'date',
            'cancelled_at' => 'immutable_datetime',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'balance_due' => 'decimal:2',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(DentalInvoiceDetail::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(DentalPayment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
