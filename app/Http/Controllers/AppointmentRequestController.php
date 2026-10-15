<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Models\AppointmentRequest;
use App\Models\Dentist;
use App\Models\Patient;
use App\Services\AppointmentScheduler;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AppointmentRequestController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('appointment_requests.view');
        $filters = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['nullable', Rule::in(['pending', 'confirmed', 'rejected'])],
            'dentist_id' => ['nullable', 'integer', Rule::exists('dentists', 'id')],
        ]);
        $requests = AppointmentRequest::query()->with(['treatment:id,name', 'dentist.user:id,name'])
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(isset($filters['dentist_id']), fn ($query) => $query->where('dentist_id', $filters['dentist_id']));

        if (isset($filters['date'])) {
            $start = CarbonImmutable::createFromFormat('!Y-m-d', $filters['date'], config('appointments.timezone'))->startOfDay();
            $requests->where('starts_at', '>=', $start->utc())->where('starts_at', '<', $start->addDay()->utc());
        }

        return view('appointment-requests.index', [
            'requests' => $requests
                ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
                ->latest('id')->paginate(20)->withQueryString(),
            'dentists' => Dentist::query()->with('user:id,name')->orderBy('id')->get(),
            'filters' => $filters,
            'pendingCount' => AppointmentRequest::query()->where('status', 'pending')->count(),
        ]);
    }

    public function show(AppointmentRequest $appointmentRequest, AppointmentScheduler $scheduler): View
    {
        Gate::authorize('appointment_requests.view');
        $appointmentRequest->load(['treatment', 'dentist.user', 'patient', 'appointment', 'events.user']);
        $matches = Patient::query()->where('is_active', true)
            ->where(function ($query) use ($appointmentRequest): void {
                $query->whereRaw('LOWER(email) = ?', [$appointmentRequest->email])
                    ->orWhere('phone', $appointmentRequest->phone);
            })->orderBy('last_name')->limit(30)->get();

        return view('appointment-requests.show', [
            'appointmentRequest' => $appointmentRequest,
            'matches' => $matches,
            'isSlotAvailable' => $scheduler->isAvailable($appointmentRequest->dentist_id, $appointmentRequest->starts_at, $appointmentRequest->ends_at),
        ]);
    }

    public function confirm(Request $request, AppointmentRequest $appointmentRequest, AppointmentScheduler $scheduler): RedirectResponse
    {
        Gate::authorize('appointment_requests.confirm');
        $data = $request->validate(['patient_id' => ['nullable', 'integer', Rule::exists('patients', 'id')->where('is_active', true)->whereNull('deleted_at')]]);

        DB::transaction(function () use ($appointmentRequest, $data, $request, $scheduler): void {
            $booking = AppointmentRequest::query()->whereKey($appointmentRequest->id)->lockForUpdate()->firstOrFail();

            if ($booking->status !== 'pending') {
                throw ValidationException::withMessages(['status' => 'La solicitud ya fue revisada.']);
            }

            if (! $booking->treatment->is_active || ! $booking->treatment->is_public) {
                throw ValidationException::withMessages(['status' => 'El tratamiento ya no está disponible.']);
            }

            $matches = Patient::query()->where('is_active', true)
                ->where(function ($query) use ($booking): void {
                    $query->whereRaw('LOWER(email) = ?', [$booking->email])->orWhere('phone', $booking->phone);
                });
            $patient = isset($data['patient_id'])
                ? $matches->whereKey($data['patient_id'])->first()
                : null;

            if (isset($data['patient_id']) && $patient === null) {
                throw ValidationException::withMessages(['patient_id' => 'Selecciona un paciente que coincida con los datos de contacto.']);
            }

            if ($patient === null && $matches->exists()) {
                throw ValidationException::withMessages(['patient_id' => 'Ya existe un paciente con ese correo o teléfono. Selecciónalo antes de confirmar.']);
            }

            if ($patient === null) {
                $parts = preg_split('/\s+/', trim($booking->name), 2);
                $patient = Patient::query()->create([
                    'first_name' => $parts[0],
                    'last_name' => $parts[1] ?? '',
                    'phone' => $booking->phone,
                    'email' => $booking->email,
                    'is_active' => true,
                ]);
            }

            $patient->dentists()->syncWithoutDetaching([$booking->dentist_id]);
            $start = $booking->starts_at->setTimezone(config('appointments.timezone'))->format('Y-m-d\TH:i');
            $end = $booking->ends_at->setTimezone(config('appointments.timezone'))->format('Y-m-d\TH:i');
            $appointment = $scheduler->create([
                'patient_id' => $patient->id,
                'dentist_id' => $booking->dentist_id,
                'starts_at' => $start,
                'ends_at' => $end,
                'planned_treatment' => $booking->treatment->name,
            ], $request->user());
            $scheduler->transition($appointment, AppointmentStatus::Confirmed, $request->user());

            $booking->update([
                'status' => 'confirmed',
                'duplicate_key' => null,
                'patient_id' => $patient->id,
                'appointment_id' => $appointment->id,
                'reviewed_by' => $request->user()->id,
            ]);
            $booking->events()->create(['user_id' => $request->user()->id, 'event_type' => 'confirmed']);
        }, 3);

        return back()->with('status', 'Solicitud confirmada y cita registrada.');
    }

    public function reject(Request $request, AppointmentRequest $appointmentRequest): RedirectResponse
    {
        Gate::authorize('appointment_requests.reject');
        $data = $request->validate(['review_note' => ['required', 'string', 'max:1000']]);

        DB::transaction(function () use ($appointmentRequest, $data, $request): void {
            $booking = AppointmentRequest::query()->whereKey($appointmentRequest->id)->lockForUpdate()->firstOrFail();

            if ($booking->status !== 'pending') {
                throw ValidationException::withMessages(['status' => 'La solicitud ya fue revisada.']);
            }

            $booking->update([
                'status' => 'rejected',
                'duplicate_key' => null,
                'reviewed_by' => $request->user()->id,
                'review_note' => $data['review_note'],
            ]);
            $booking->events()->create(['user_id' => $request->user()->id, 'event_type' => 'rejected', 'note' => $data['review_note']]);
        });

        return back()->with('status', 'Solicitud rechazada.');
    }
}
