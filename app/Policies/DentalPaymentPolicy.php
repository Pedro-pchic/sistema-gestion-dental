<?php

namespace App\Policies;

use App\Models\DentalPayment;
use App\Models\Role;
use App\Models\User;

class DentalPaymentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->canManageDentalFinances($user);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, DentalPayment $dentalPayment): bool
    {
        return $this->canManageDentalFinances($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, DentalPayment $dentalPayment): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, DentalPayment $dentalPayment): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, DentalPayment $dentalPayment): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, DentalPayment $dentalPayment): bool
    {
        return false;
    }

    public function cancel(User $user, DentalPayment $dentalPayment): bool
    {
        return $user->is_active && $user->hasRole(Role::ADMINISTRATOR);
    }

    private function canManageDentalFinances(User $user): bool
    {
        return $user->is_active && in_array($user->role?->name, [
            Role::ADMINISTRATOR,
            Role::RECEPTIONIST,
        ], true);
    }
}
