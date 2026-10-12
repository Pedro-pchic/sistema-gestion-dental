<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'appointment_reminder_id',
    'attempt_number',
    'result',
    'attempted_at',
    'completed_at',
    'error_type',
])]
class AppointmentReminderAttempt extends Model
{
    protected function casts(): array
    {
        return [
            'attempt_number' => 'integer',
            'attempted_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
        ];
    }

    public function reminder(): BelongsTo
    {
        return $this->belongsTo(AppointmentReminder::class, 'appointment_reminder_id');
    }
}
