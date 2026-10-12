<?php

namespace App\Models;

use App\Enums\TreatmentPlanStatus;
use Database\Factories\TreatmentPlanItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['treatment_plan_id', 'treatment_id', 'quantity', 'estimated_unit_price', 'status', 'sort_order'])]
class TreatmentPlanItem extends Model
{
    /** @use HasFactory<TreatmentPlanItemFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'estimated_unit_price' => 'decimal:2',
            'status' => TreatmentPlanStatus::class,
            'sort_order' => 'integer',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlan::class, 'treatment_plan_id');
    }

    public function treatment(): BelongsTo
    {
        return $this->belongsTo(Treatment::class);
    }

    public function patientTreatments(): HasMany
    {
        return $this->hasMany(PatientTreatment::class, 'treatment_plan_item_id');
    }
}
