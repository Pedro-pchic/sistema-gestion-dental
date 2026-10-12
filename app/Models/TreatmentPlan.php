<?php

namespace App\Models;

use App\Enums\TreatmentPlanStatus;
use Database\Factories\TreatmentPlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['patient_id', 'dentist_id', 'created_by', 'updated_by', 'title', 'description', 'status'])]
class TreatmentPlan extends Model
{
    /** @use HasFactory<TreatmentPlanFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => TreatmentPlanStatus::class,
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TreatmentPlanItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function treatmentPlanItems(): HasMany
    {
        return $this->items();
    }
}
