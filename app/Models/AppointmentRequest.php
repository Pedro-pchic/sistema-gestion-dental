<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['treatment_id', 'dentist_id', 'starts_at', 'ends_at', 'name', 'phone', 'email', 'status', 'duplicate_key', 'patient_id', 'appointment_id', 'reviewed_by', 'review_note'])]
class AppointmentRequest extends Model
{
    protected function casts(): array
    {
        return ['starts_at' => 'immutable_datetime', 'ends_at' => 'immutable_datetime'];
    }

    public function treatment(): BelongsTo
    {
        return $this->belongsTo(Treatment::class);
    }

    public function dentist(): BelongsTo
    {
        return $this->belongsTo(Dentist::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(AppointmentRequestEvent::class)->latest('id');
    }
}
