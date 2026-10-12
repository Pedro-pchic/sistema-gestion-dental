<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\Role;
use App\Models\User;

class PatientPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role?->name, [
            Role::ADMINISTRATOR,
            Role::DENTIST,
            Role::RECEPTIONIST,
        ], true);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Patient $patient): bool
    {
        if ($user->hasRole(Role::ADMINISTRATOR) || $user->hasRole(Role::RECEPTIONIST)) {
            return true;
        }

        if (! $user->hasRole(Role::DENTIST)) {
            return false;
        }

        return $patient->dentists()
            ->where('dentists.user_id', $user->id)
            ->where('dentists.is_active', true)
            ->exists();
    }

    public function viewClinicalHistory(User $user, Patient $patient): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->hasRole(Role::ADMINISTRATOR)) {
            return (bool) $user->can_view_clinical_records;
        }

        return $this->isAssignedActiveDentist($user, $patient);
    }

    public function createClinicalRecord(User $user, Patient $patient): bool
    {
        return $this->isAssignedActiveDentist($user, $patient);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(Role::ADMINISTRATOR)
            || $user->hasRole(Role::RECEPTIONIST);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Patient $patient): bool
    {
        return $this->create($user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Patient $patient): bool
    {
        return $this->create($user);
    }

    public function assignDentists(User $user): bool
    {
        return $user->hasRole(Role::ADMINISTRATOR);
    }

    private function isAssignedActiveDentist(User $user, Patient $patient): bool
    {
        if (! $user->is_active || ! $user->hasRole(Role::DENTIST)) {
            return false;
        }

        $dentistId = $user->dentist()
            ->where('is_active', true)
            ->value('id');

        return $dentistId !== null && $patient->dentists()->whereKey($dentistId)->exists();
    }
}
