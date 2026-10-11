<?php

namespace App\Policies;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role?->name, [Role::ADMINISTRATOR, Role::DENTIST, Role::RECEPTIONIST], true);
    }

    public function view(User $user, Appointment $appointment): bool
    {
        if ($this->canManage($user)) {
            return true;
        }

        return $user->hasRole(Role::DENTIST) && $this->isAssignedDentist($user, $appointment);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function reschedule(User $user, Appointment $appointment): bool
    {
        return $this->canManage($user)
            && in_array($appointment->status, [AppointmentStatus::Scheduled, AppointmentStatus::Confirmed], true);
    }

    public function cancel(User $user, Appointment $appointment): bool
    {
        return $this->canManage($user) && $appointment->status->canTransitionTo(AppointmentStatus::Cancelled);
    }

    public function confirm(User $user, Appointment $appointment): bool
    {
        return $this->canManage($user) && $appointment->status->canTransitionTo(AppointmentStatus::Confirmed);
    }

    public function start(User $user, Appointment $appointment): bool
    {
        return $appointment->status->canTransitionTo(AppointmentStatus::InTreatment)
            && ($user->hasRole(Role::ADMINISTRATOR) || $this->isAssignedDentist($user, $appointment));
    }

    public function complete(User $user, Appointment $appointment): bool
    {
        return $appointment->status->canTransitionTo(AppointmentStatus::Completed)
            && ($user->hasRole(Role::ADMINISTRATOR) || $this->isAssignedDentist($user, $appointment));
    }

    public function markNoShow(User $user, Appointment $appointment): bool
    {
        return $this->canManage($user)
            && $appointment->status->canTransitionTo(AppointmentStatus::NoShow)
            && $appointment->starts_at->lessThanOrEqualTo(now('UTC'));
    }

    private function canManage(User $user): bool
    {
        return $user->hasRole(Role::ADMINISTRATOR) || $user->hasRole(Role::RECEPTIONIST);
    }

    private function isAssignedDentist(User $user, Appointment $appointment): bool
    {
        return $user->hasRole(Role::DENTIST)
            && $appointment->dentist()
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->whereHas('user', fn (Builder $query): Builder => $query->where('is_active', true))
                ->exists();
    }
}
