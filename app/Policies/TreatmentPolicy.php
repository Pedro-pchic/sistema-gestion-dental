<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\Treatment;
use App\Models\User;

class TreatmentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->is_active && in_array($user->role?->name, [
            Role::ADMINISTRATOR,
            Role::DENTIST,
            Role::RECEPTIONIST,
        ], true);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Treatment $treatment): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->is_active && $user->hasRole(Role::ADMINISTRATOR);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Treatment $treatment): bool
    {
        return $this->create($user);
    }

    public function toggleStatus(User $user, Treatment $treatment): bool
    {
        return $this->create($user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Treatment $treatment): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Treatment $treatment): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Treatment $treatment): bool
    {
        return false;
    }
}
