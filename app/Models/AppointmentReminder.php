<?php

namespace App\Models;

use App\Enums\AppointmentReminderStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'appointment_id',
    'patient_id',
    'channel',
    'appointment_starts_at',
    'scheduled_at',
    'status',
    'attempts',
    'next_attempt_at',
    'queued_at',
    'claimed_at',
    'sent_at',
    'last_error',
])]
class AppointmentReminder extends Model
{
    protected function casts(): array
    {
        return [
            'appointment_starts_at' => 'immutable_datetime',
            'scheduled_at' => 'immutable_datetime',
            'status' => AppointmentReminderStatus::class,
            'attempts' => 'integer',
            'next_attempt_at' => 'immutable_datetime',
            'queued_at' => 'immutable_datetime',
            'claimed_at' => 'immutable_datetime',
            'sent_at' => 'immutable_datetime',
        ];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(AppointmentReminderAttempt::class);
    }
}
