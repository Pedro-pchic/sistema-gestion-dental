<?php

namespace App\Models;

use Database\Factories\DentalInvoiceDetailFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'dental_invoice_id',
    'patient_treatment_id',
    'description',
    'quantity',
    'unit_price',
    'subtotal',
])]
class DentalInvoiceDetail extends Model
{
    /** @use HasFactory<DentalInvoiceDetailFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(DentalInvoice::class, 'dental_invoice_id');
    }

    public function patientTreatment(): BelongsTo
    {
        return $this->belongsTo(PatientTreatment::class);
    }
}
