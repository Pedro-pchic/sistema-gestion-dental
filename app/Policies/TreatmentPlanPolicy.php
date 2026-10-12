<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\Role;
use App\Models\TreatmentPlan;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class TreatmentPlanPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->is_active
            && ($user->hasRole(Role::DENTIST)
                || ($user->hasRole(Role::ADMINISTRATOR) && $user->can_view_clinical_records));
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TreatmentPlan $treatmentPlan): bool
    {
        return Gate::allows('viewClinicalHistory', $treatmentPlan->patient);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Patient $patient): bool
    {
        return Gate::allows('createClinicalRecord', $patient);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TreatmentPlan $treatmentPlan): bool
    {
        return $user->hasRole(Role::DENTIST)
            && $treatmentPlan->dentist()->where('user_id', $user->id)->exists()
            && Gate::allows('createClinicalRecord', $treatmentPlan->patient);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TreatmentPlan $treatmentPlan): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, TreatmentPlan $treatmentPlan): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, TreatmentPlan $treatmentPlan): bool
    {
        return false;
    }
}
