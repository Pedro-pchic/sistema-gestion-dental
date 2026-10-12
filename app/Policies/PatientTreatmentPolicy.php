<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\PatientTreatment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class PatientTreatmentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PatientTreatment $patientTreatment): bool
    {
        return Gate::allows('viewClinicalHistory', $patientTreatment->patient);
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
    public function update(User $user, PatientTreatment $patientTreatment): bool
    {
        return $user->hasRole(Role::DENTIST)
            && $patientTreatment->dentist()->where('user_id', $user->id)->exists()
            && Gate::allows('createClinicalRecord', $patientTreatment->patient);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PatientTreatment $patientTreatment): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PatientTreatment $patientTreatment): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PatientTreatment $patientTreatment): bool
    {
        return false;
    }
}
