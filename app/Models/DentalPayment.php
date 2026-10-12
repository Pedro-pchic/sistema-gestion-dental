<?php

namespace App\Models;

use App\Enums\DentalPaymentStatus;
use App\Enums\PaymentMethod;
use Database\Factories\DentalPaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'dental_invoice_id',
    'number',
    'idempotency_key',
    'payment_date',
    'amount',
    'method',
    'reference',
    'notes',
    'status',
    'recorded_by',
    'cancelled_by',
    'cancelled_at',
    'cancellation_reason',
])]
class DentalPayment extends Model
{
    /** @use HasFactory<DentalPaymentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
            'method' => PaymentMethod::class,
            'status' => DentalPaymentStatus::class,
            'cancelled_at' => 'immutable_datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(DentalInvoice::class, 'dental_invoice_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
