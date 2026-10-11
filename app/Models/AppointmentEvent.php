<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['appointment_id', 'user_id', 'event_type', 'from_status', 'to_status', 'changes', 'reason'])]
class AppointmentEvent extends Model
{
    public const string CREATED = 'created';

    public const string RESCHEDULED = 'rescheduled';

    public const string CONFIRMED = 'confirmed';

    public const string STARTED = 'started';

    public const string COMPLETED = 'completed';

    public const string CANCELLED = 'cancelled';

    public const string NO_SHOW = 'no_show';

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'changes' => 'array',
        ];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
