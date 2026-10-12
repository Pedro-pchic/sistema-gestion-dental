<?php

namespace App\Models;

use Database\Factories\PatientTreatmentEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['patient_treatment_id', 'user_id', 'event_type', 'changes'])]
class PatientTreatmentEvent extends Model
{
    /** @use HasFactory<PatientTreatmentEventFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'changes' => 'array',
        ];
    }

    public function patientTreatment(): BelongsTo
    {
        return $this->belongsTo(PatientTreatment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
