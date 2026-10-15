<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Http\Requests\RescheduleAppointmentRequest;
use App\Http\Requests\StoreAppointmentRequest;
use App\Models\Appointment;
use App\Models\AppointmentRequest;
use App\Models\Dentist;
use App\Models\Patient;
use App\Models\Role;
use App\Services\AppointmentScheduler;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Appointment::class);

        $user = $request->user();
        $filters = $request->validate([
            'view' => ['nullable', Rule::in(['day', 'week', 'month'])],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['nullable', Rule::in(array_map(
                fn (AppointmentStatus $status): string => $status->value,
                AppointmentStatus::cases(),
            ))],
            'dentist_id' => $user->hasRole(Role::DENTIST)
                ? ['prohibited']
                : ['nullable', 'integer', Rule::exists('dentists', 'id')],
            'search' => ['nullable', 'string', 'max:100'],
            'treatment' => ['nullable', 'string', 'max:100'],
        ]);

        $timezone = config('appointments.timezone');
        $viewMode = $filters['view'] ?? 'day';
        $focusDate = isset($filters['date'])
            ? CarbonImmutable::createFromFormat('!Y-m-d', $filters['date'], $timezone)
            : CarbonImmutable::now($timezone)->startOfDay();

        if ($viewMode === 'day') {
            $rangeStart = $focusDate->startOfDay();
            $rangeEnd = $rangeStart->addDay();
        } elseif ($viewMode === 'week') {
            $rangeStart = $focusDate->startOfWeek(CarbonInterface::MONDAY);
            $rangeEnd = $rangeStart->addWeek();
        } else {
            $rangeStart = $focusDate->startOfMonth()->startOfWeek(CarbonInterface::MONDAY);
            $rangeEnd = $focusDate->endOfMonth()->endOfWeek(CarbonInterface::MONDAY)->addDay();
        }

        $appointmentsQuery = Appointment::query()
            ->with([
                'patient:id,record_number,first_name,last_name',
                'dentist:id,user_id',
                'dentist.user:id,name',
            ])
            ->where('starts_at', '>=', $rangeStart->utc())
            ->where('starts_at', '<', $rangeEnd->utc());

        if ($user->hasRole(Role::DENTIST)) {
            $dentist = $user->dentist;

            if ($dentist === null || ! $dentist->is_active || ! $user->is_active) {
                $appointmentsQuery->whereKey(0);
            } else {
                $appointmentsQuery->where('dentist_id', $dentist->id);
            }
        } elseif (isset($filters['dentist_id'])) {
            $appointmentsQuery->where('dentist_id', $filters['dentist_id']);
        }

        if (isset($filters['status'])) {
            $appointmentsQuery->where('status', $filters['status']);
        }

        if (filled($filters['treatment'] ?? null)) {
            $appointmentsQuery->whereRaw('LOWER(planned_treatment) LIKE ?', [
                '%'.mb_strtolower(trim($filters['treatment'])).'%',
            ]);
        }

        $terms = preg_split('/\s+/', trim((string) ($filters['search'] ?? '')), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($terms as $term) {
            $value = '%'.mb_strtolower($term).'%';

            $appointmentsQuery->where(function (Builder $query) use ($value): void {
                $query->whereHas('patient', function (Builder $patientQuery) use ($value): void {
                    $patientQuery->whereRaw('LOWER(first_name) LIKE ?', [$value])
                        ->orWhereRaw('LOWER(last_name) LIKE ?', [$value])
                        ->orWhereRaw('LOWER(record_number) LIKE ?', [$value]);
                })->orWhereHas('dentist.user', function (Builder $userQuery) use ($value): void {
                    $userQuery->whereRaw('LOWER(name) LIKE ?', [$value]);
                });
            });
        }

        $appointments = $appointmentsQuery->orderBy('starts_at')->get();
        $appointmentsByDate = $appointments->groupBy(
            fn (Appointment $appointment): string => $appointment->starts_at
                ->setTimezone($timezone)
                ->toDateString(),
        );
        $calendarDays = [];

        for ($day = $rangeStart; $day->lessThan($rangeEnd); $day = $day->addDay()) {
            $calendarDays[] = [
                'date' => $day,
                'inCurrentMonth' => $day->format('Y-m') === $focusDate->format('Y-m'),
            ];
        }

        $dentists = $user->hasRole(Role::DENTIST)
            ? collect()
            : Dentist::query()
                ->with('user:id,name')
                ->where('is_active', true)
                ->whereHas('user', fn (Builder $query): Builder => $query->where('is_active', true))
                ->whereHas('user.role', fn (Builder $query): Builder => $query->where('name', Role::DENTIST))
                ->orderBy('id')
                ->get();

        $availabilityDentistId = $user->hasRole(Role::DENTIST)
            ? ($user->dentist?->is_active && $user->is_active ? $user->dentist->id : null)
            : ($filters['dentist_id'] ?? null);
        $occupiedAppointments = collect();
        $freeIntervals = [];

        if ($viewMode === 'day' && $availabilityDentistId !== null) {
            $occupiedAppointments = Appointment::query()
                ->where('dentist_id', $availabilityDentistId)
                ->whereIn('status', AppointmentStatus::reservableValues())
                ->where('starts_at', '<', $rangeEnd->utc())
                ->where('ends_at', '>', $rangeStart->utc())
                ->orderBy('starts_at')
                ->get(['id', 'starts_at', 'ends_at']);
            $previousEnd = null;

            foreach ($occupiedAppointments as $occupiedAppointment) {
                $start = $occupiedAppointment->starts_at->max($rangeStart->utc());
                $end = $occupiedAppointment->ends_at->min($rangeEnd->utc());

                if ($previousEnd !== null && $previousEnd->lessThan($start)) {
                    $freeIntervals[] = ['starts_at' => $previousEnd, 'ends_at' => $start];
                }

                $previousEnd = $previousEnd?->max($end) ?? $end;
            }
        }

        return view('appointments.index', [
            'appointments' => $appointments,
            'appointmentsByDate' => $appointmentsByDate,
            'calendarDays' => $calendarDays,
            'dentists' => $dentists,
            'availabilityDentistId' => $availabilityDentistId,
            'occupiedAppointments' => $occupiedAppointments,
            'freeIntervals' => $freeIntervals,
            'filters' => $filters,
            'focusDate' => $focusDate,
            'rangeStart' => $rangeStart,
            'rangeEnd' => $rangeEnd,
            'timezone' => $timezone,
            'viewMode' => $viewMode,
            'weekdayLabels' => ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
            'monthLabels' => [
                1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
                5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
                9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
            ],
            'previousDate' => $viewMode === 'day'
                ? $focusDate->subDay()->toDateString()
                : ($viewMode === 'week'
                    ? $focusDate->subWeek()->toDateString()
                    : $focusDate->subMonthNoOverflow()->toDateString()),
            'nextDate' => $viewMode === 'day'
                ? $focusDate->addDay()->toDateString()
                : ($viewMode === 'week'
                    ? $focusDate->addWeek()->toDateString()
                    : $focusDate->addMonthNoOverflow()->toDateString()),
            'statusOptions' => AppointmentStatus::cases(),
            'pendingPublicRequests' => $user->can('appointment_requests.view')
                ? AppointmentRequest::query()->where('status', 'pending')->count()
                : null,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Appointment::class);

        $timezone = config('appointments.timezone');
        $defaultStart = CarbonImmutable::now($timezone)->addHour()->startOfHour();

        return view('appointments.create', [
            'patients' => Patient::query()
                ->where('is_active', true)
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(['id', 'record_number', 'first_name', 'last_name']),
            'dentists' => Dentist::query()
                ->with('user:id,name')
                ->where('is_active', true)
                ->whereHas('user', fn (Builder $query): Builder => $query->where('is_active', true))
                ->whereHas('user.role', fn (Builder $query): Builder => $query->where('name', Role::DENTIST))
                ->orderBy('id')
                ->get(),
            'timezone' => $timezone,
            'defaultStartsAt' => $defaultStart->format('Y-m-d\\TH:i'),
            'defaultEndsAt' => $defaultStart->addHour()->format('Y-m-d\\TH:i'),
        ]);
    }

    public function store(StoreAppointmentRequest $request, AppointmentScheduler $scheduler): RedirectResponse
    {
        $appointment = $scheduler->create($request->validated(), $request->user());

        return redirect()
            ->route('citas.show', $appointment)
            ->with('status', 'Cita programada correctamente.');
    }

    public function show(Appointment $appointment): View
    {
        Gate::authorize('view', $appointment);
        $appointment->load(['patient', 'dentist.user', 'creator', 'canceller', 'events.user']);

        return view('appointments.show', [
            'appointment' => $appointment,
            'timezone' => config('appointments.timezone'),
        ]);
    }

    public function edit(Appointment $appointment): View
    {
        Gate::authorize('reschedule', $appointment);
        $timezone = config('appointments.timezone');
        $appointment->load(['patient', 'dentist.user']);

        return view('appointments.reschedule', [
            'appointment' => $appointment,
            'timezone' => $timezone,
            'startsAt' => $appointment->starts_at->setTimezone($timezone)->format('Y-m-d\\TH:i'),
            'endsAt' => $appointment->ends_at->setTimezone($timezone)->format('Y-m-d\\TH:i'),
        ]);
    }

    public function update(
        RescheduleAppointmentRequest $request,
        Appointment $appointment,
        AppointmentScheduler $scheduler,
    ): RedirectResponse {
        $appointment = $scheduler->reschedule($appointment, $request->validated(), $request->user());

        return redirect()
            ->route('citas.show', $appointment)
            ->with('status', 'Cita reprogramada correctamente.');
    }
}
