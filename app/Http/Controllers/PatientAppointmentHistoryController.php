<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Models\Patient;
use App\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PatientAppointmentHistoryController extends Controller
{
    public function index(Patient $patient): View
    {
        Gate::authorize('view', $patient);

        $user = request()->user();
        $appointments = $patient->appointments()
            ->with([
                'dentist:id,user_id',
                'dentist.user:id,name',
                'creator:id,name',
            ])
            ->when($user->hasRole(Role::DENTIST), function (Builder $query) use ($user): void {
                $dentist = $user->dentist;

                if ($dentist === null || ! $dentist->is_active || ! $user->is_active) {
                    $query->whereKey(0);

                    return;
                }

                $query->where('dentist_id', $dentist->id);
            })
            ->orderByDesc('starts_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('appointments.history', [
            'appointments' => $appointments,
            'patient' => $patient,
            'timezone' => config('appointments.timezone'),
            'statusOptions' => AppointmentStatus::cases(),
        ]);
    }
}
