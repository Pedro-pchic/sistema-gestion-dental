<?php

namespace App\Policies;

use App\Models\DentalInvoice;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;

class DentalInvoicePolicy
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
    public function view(User $user, DentalInvoice $dentalInvoice): bool
    {
        return $this->canManageDentalFinances($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->canManageDentalFinances($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, DentalInvoice $dentalInvoice): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, DentalInvoice $dentalInvoice): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, DentalInvoice $dentalInvoice): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, DentalInvoice $dentalInvoice): bool
    {
        return false;
    }

    public function issue(User $user, DentalInvoice $dentalInvoice): bool
    {
        return $this->canManageDentalFinances($user);
    }

    public function recordPayment(User $user, DentalInvoice $dentalInvoice): bool
    {
        return $this->canManageDentalFinances($user);
    }

    public function cancel(User $user, DentalInvoice $dentalInvoice): bool
    {
        return $user->hasRole(Role::ADMINISTRATOR);
    }

    public function viewPatientSummary(User $user, Patient $patient): bool
    {
        if ($this->canManageDentalFinances($user)) {
            return true;
        }

        return $user->is_active
            && $user->hasRole(Role::DENTIST)
            && $patient->dentists()
                ->where('dentists.user_id', $user->getKey())
                ->where('dentists.is_active', true)
                ->exists();
    }

    private function canManageDentalFinances(User $user): bool
    {
        return $user->is_active && in_array($user->role?->name, [
            Role::ADMINISTRATOR,
            Role::RECEPTIONIST,
        ], true);
    }
}
