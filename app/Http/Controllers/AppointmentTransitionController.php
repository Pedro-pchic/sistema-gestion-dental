<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Http\Requests\CancelAppointmentRequest;
use App\Models\Appointment;
use App\Services\AppointmentScheduler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class AppointmentTransitionController extends Controller
{
    public function confirm(Appointment $appointment, AppointmentScheduler $scheduler): RedirectResponse
    {
        Gate::authorize('confirm', $appointment);
        $scheduler->transition($appointment, AppointmentStatus::Confirmed, request()->user());

        return back()->with('status', 'Asistencia confirmada.');
    }

    public function start(Appointment $appointment, AppointmentScheduler $scheduler): RedirectResponse
    {
        Gate::authorize('start', $appointment);
        $scheduler->transition($appointment, AppointmentStatus::InTreatment, request()->user());

        return back()->with('status', 'Atención iniciada.');
    }

    public function complete(Appointment $appointment, AppointmentScheduler $scheduler): RedirectResponse
    {
        Gate::authorize('complete', $appointment);
        $scheduler->transition($appointment, AppointmentStatus::Completed, request()->user());

        return back()->with('status', 'Atención completada.');
    }

    public function cancel(
        CancelAppointmentRequest $request,
        Appointment $appointment,
        AppointmentScheduler $scheduler,
    ): RedirectResponse {
        $scheduler->transition(
            $appointment,
            AppointmentStatus::Cancelled,
            $request->user(),
            $request->validated('reason'),
        );

        return back()->with('status', 'Cita cancelada y motivo registrado.');
    }

    public function markNoShow(Appointment $appointment, AppointmentScheduler $scheduler): RedirectResponse
    {
        Gate::authorize('markNoShow', $appointment);
        $scheduler->transition($appointment, AppointmentStatus::NoShow, request()->user());

        return back()->with('status', 'Cita marcada como inasistencia.');
    }
}
