<?php

namespace App\Models;

use Database\Factories\PatientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable([
    'first_name',
    'last_name',
    'date_of_birth',
    'phone',
    'address',
    'email',
    'is_active',
    'email_reminders_enabled',
    'email_reminders_consent_at',
    'email_reminders_revoked_at',
    'sms_reminders_enabled',
    'sms_reminders_consent_at',
    'sms_reminders_revoked_at',
    'notification_preferences_updated_by',
])]
class Patient extends Model
{
    /** @use HasFactory<PatientFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'is_active' => 'boolean',
            'email_reminders_enabled' => 'boolean',
            'email_reminders_consent_at' => 'immutable_datetime',
            'email_reminders_revoked_at' => 'immutable_datetime',
            'sms_reminders_enabled' => 'boolean',
            'sms_reminders_consent_at' => 'immutable_datetime',
            'sms_reminders_revoked_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Patient $patient): void {
            if (blank($patient->record_number)) {
                $patient->record_number = (string) Str::ulid();
            }
        });
    }

    /** @param Builder<Patient> $query */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        $terms = preg_split('/\s+/', trim((string) $search), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($terms as $term) {
            $value = '%'.mb_strtolower($term).'%';

            $query->where(function (Builder $query) use ($value): void {
                $query->whereRaw('LOWER(first_name) LIKE ?', [$value])
                    ->orWhereRaw('LOWER(last_name) LIKE ?', [$value])
                    ->orWhereRaw("LOWER(COALESCE(phone, '')) LIKE ?", [$value]);
            });
        }

        return $query;
    }

    public function dentists(): BelongsToMany
    {
        return $this->belongsToMany(Dentist::class)->withTimestamps();
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function canReceiveAppointmentReminder(string $channel): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($channel === 'email') {
            return $this->email_reminders_enabled
                && $this->email_reminders_consent_at !== null
                && $this->email_reminders_revoked_at === null
                && is_string($this->email)
                && filter_var($this->email, FILTER_VALIDATE_EMAIL) !== false;
        }

        return $channel === 'sms'
            && $this->sms_reminders_enabled
            && $this->sms_reminders_consent_at !== null
            && $this->sms_reminders_revoked_at === null
            && is_string($this->phone)
            && preg_match('/^\+?[0-9][0-9\s().-]{5,38}[0-9]$/', $this->phone) === 1
            && preg_match_all('/[0-9]/', $this->phone) >= 7;
    }

    public function patientTreatments(): HasMany
    {
        return $this->hasMany(PatientTreatment::class);
    }

    public function dentalInvoices(): HasMany
    {
        return $this->hasMany(DentalInvoice::class);
    }

    public function treatmentPlans(): HasMany
    {
        return $this->hasMany(TreatmentPlan::class);
    }
}
