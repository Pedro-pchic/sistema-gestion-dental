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
use Illuminate\Support\Str;

#[Fillable(['first_name', 'last_name', 'date_of_birth', 'phone', 'address', 'email', 'is_active'])]
class Patient extends Model
{
    /** @use HasFactory<PatientFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'is_active' => 'boolean',
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
}
