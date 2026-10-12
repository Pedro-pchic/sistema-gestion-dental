<?php

namespace App\Models;

use Database\Factories\PatientTreatmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'patient_id',
    'dentist_id',
    'treatment_id',
    'appointment_id',
    'treatment_plan_item_id',
    'registered_by',
    'updated_by',
    'performed_at',
    'procedure_description',
    'clinical_notes',
    'price_applied',
])]
class PatientTreatment extends Model
{
    /** @use HasFactory<PatientTreatmentFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'performed_at' => 'immutable_datetime',
            'price_applied' => 'decimal:2',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function dentist(): BelongsTo
    {
        return $this->belongsTo(Dentist::class);
    }

    public function treatment(): BelongsTo
    {
        return $this->belongsTo(Treatment::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function planItem(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlanItem::class, 'treatment_plan_item_id');
    }

    public function registrar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function events(): HasMany
    {
        return $this->hasMany(PatientTreatmentEvent::class)->latest('id');
    }
}
