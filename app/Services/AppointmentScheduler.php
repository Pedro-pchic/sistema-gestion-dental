<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AppointmentEvent;
use App\Models\Dentist;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentScheduler
{
    /**
     * @param  array{patient_id: int|string, dentist_id: int|string, starts_at: string, ends_at: string, planned_treatment: string, administrative_notes?: ?string}  $data
     */
    public function create(array $data, User $actor): Appointment
    {
        $startsAt = $this->parseLocalDateTime($data['starts_at']);
        $endsAt = $this->parseLocalDateTime($data['ends_at']);
        $this->assertValidFutureInterval($startsAt, $endsAt);

        try {
            $appointment = DB::transaction(function () use ($data, $actor, $startsAt, $endsAt): Appointment {
                $dentist = $this->lockActiveDentist((int) $data['dentist_id']);
                $patient = Patient::query()
                    ->whereKey((int) $data['patient_id'])
                    ->lockForUpdate()
                    ->first();

                if ($patient === null || ! $patient->is_active) {
                    throw ValidationException::withMessages([
                        'patient_id' => 'Selecciona un paciente activo.',
                    ]);
                }

                if (! $patient->dentists()->where('dentists.id', $dentist->id)->exists()) {
                    throw ValidationException::withMessages([
                        'dentist_id' => 'El odontólogo debe estar asignado previamente a este paciente.',
                    ]);
                }

                $this->assertNoOverlap($dentist->id, $startsAt, $endsAt);

                $appointment = Appointment::query()->create([
                    'patient_id' => $patient->id,
                    'dentist_id' => $dentist->id,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'planned_treatment' => $data['planned_treatment'],
                    'administrative_notes' => $data['administrative_notes'] ?? null,
                    'status' => AppointmentStatus::Scheduled,
                    'created_by' => $actor->id,
                ]);

                $this->recordEvent(
                    $appointment,
                    $actor,
                    AppointmentEvent::CREATED,
                    null,
                    AppointmentStatus::Scheduled,
                    ['starts_at' => $startsAt->toIso8601String(), 'ends_at' => $endsAt->toIso8601String()],
                );

                return $appointment;
            }, 3);
        } catch (QueryException $exception) {
            $this->rethrowUnlessOverlapViolation($exception);
        }

        return $appointment->fresh(['patient', 'dentist.user', 'creator']);
    }

    /**
     * @param  array{starts_at: string, ends_at: string}  $data
     */
    public function reschedule(Appointment $appointment, array $data, User $actor): Appointment
    {
        $startsAt = $this->parseLocalDateTime($data['starts_at']);
        $endsAt = $this->parseLocalDateTime($data['ends_at']);
        $this->assertValidFutureInterval($startsAt, $endsAt);

        try {
            $appointment = DB::transaction(function () use ($appointment, $actor, $startsAt, $endsAt): Appointment {
                $lockedAppointment = Appointment::query()
                    ->whereKey($appointment->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! in_array($lockedAppointment->status, [AppointmentStatus::Scheduled, AppointmentStatus::Confirmed], true)) {
                    throw ValidationException::withMessages([
                        'starts_at' => 'Solo se pueden reprogramar citas programadas o confirmadas.',
                    ]);
                }

                $dentist = $this->lockActiveDentist($lockedAppointment->dentist_id);
                $patient = Patient::query()
                    ->whereKey($lockedAppointment->patient_id)
                    ->lockForUpdate()
                    ->first();

                if ($patient === null || ! $patient->is_active) {
                    throw ValidationException::withMessages([
                        'starts_at' => 'No se puede reprogramar una cita de un paciente inactivo.',
                    ]);
                }

                $oldStartsAt = $lockedAppointment->starts_at;
                $oldEndsAt = $lockedAppointment->ends_at;
                $this->assertNoOverlap($dentist->id, $startsAt, $endsAt, $lockedAppointment->id);

                $lockedAppointment->update([
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                ]);

                $this->recordEvent(
                    $lockedAppointment,
                    $actor,
                    AppointmentEvent::RESCHEDULED,
                    $lockedAppointment->status,
                    $lockedAppointment->status,
                    [
                        'starts_at' => [
                            'from' => $oldStartsAt->toIso8601String(),
                            'to' => $startsAt->toIso8601String(),
                        ],
                        'ends_at' => [
                            'from' => $oldEndsAt->toIso8601String(),
                            'to' => $endsAt->toIso8601String(),
                        ],
                    ],
                );

                return $lockedAppointment;
            }, 3);
        } catch (QueryException $exception) {
            $this->rethrowUnlessOverlapViolation($exception);
        }

        return $appointment->fresh(['patient', 'dentist.user', 'creator']);
    }

    public function transition(
        Appointment $appointment,
        AppointmentStatus $nextStatus,
        User $actor,
        ?string $reason = null,
    ): Appointment {
        $appointment = DB::transaction(function () use ($appointment, $actor, $nextStatus, $reason): Appointment {
            $lockedAppointment = Appointment::query()
                ->whereKey($appointment->id)
                ->lockForUpdate()
                ->firstOrFail();
            $previousStatus = $lockedAppointment->status;

            if (! $previousStatus->canTransitionTo($nextStatus)) {
                throw ValidationException::withMessages([
                    'status' => 'La cita no permite esa transición de estado.',
                ]);
            }

            if ($nextStatus === AppointmentStatus::NoShow && $lockedAppointment->starts_at->isFuture()) {
                throw ValidationException::withMessages([
                    'status' => 'No se puede marcar como inasistencia antes de la hora de la cita.',
                ]);
            }

            $attributes = ['status' => $nextStatus];
            $eventType = match ($nextStatus) {
                AppointmentStatus::Confirmed => AppointmentEvent::CONFIRMED,
                AppointmentStatus::InTreatment => AppointmentEvent::STARTED,
                AppointmentStatus::Completed => AppointmentEvent::COMPLETED,
                AppointmentStatus::Cancelled => AppointmentEvent::CANCELLED,
                AppointmentStatus::NoShow => AppointmentEvent::NO_SHOW,
                AppointmentStatus::Scheduled => AppointmentEvent::CREATED,
            };

            if ($nextStatus === AppointmentStatus::Confirmed) {
                $attributes['confirmed_at'] = now('UTC');
            } elseif ($nextStatus === AppointmentStatus::InTreatment) {
                $attributes['started_at'] = now('UTC');
            } elseif ($nextStatus === AppointmentStatus::Completed) {
                $attributes['completed_at'] = now('UTC');
            } elseif ($nextStatus === AppointmentStatus::Cancelled) {
                $attributes['cancelled_at'] = now('UTC');
                $attributes['cancelled_by'] = $actor->id;
                $attributes['cancellation_reason'] = $reason;
            } elseif ($nextStatus === AppointmentStatus::NoShow) {
                $attributes['no_show_at'] = now('UTC');
            }

            $lockedAppointment->update($attributes);

            $this->recordEvent(
                $lockedAppointment,
                $actor,
                $eventType,
                $previousStatus,
                $nextStatus,
                [],
                $reason,
            );

            return $lockedAppointment;
        }, 3);

        return $appointment->fresh(['patient', 'dentist.user', 'creator', 'events.user']);
    }

    private function lockActiveDentist(int $dentistId): Dentist
    {
        $dentist = Dentist::query()
            ->whereKey($dentistId)
            ->lockForUpdate()
            ->first();

        if (
            $dentist === null
            || ! $dentist->is_active
            || ! $dentist->user?->is_active
            || ! $dentist->user?->hasRole(Role::DENTIST)
        ) {
            throw ValidationException::withMessages([
                'dentist_id' => 'Selecciona un odontólogo activo.',
            ]);
        }

        return $dentist;
    }

    private function assertNoOverlap(
        int $dentistId,
        CarbonImmutable $startsAt,
        CarbonImmutable $endsAt,
        ?int $exceptAppointmentId = null,
    ): void {
        $conflicts = Appointment::query()
            ->where('dentist_id', $dentistId)
            ->whereIn('status', AppointmentStatus::reservableValues())
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->when($exceptAppointmentId !== null, fn ($query) => $query->whereKeyNot($exceptAppointmentId))
            ->exists();

        if ($conflicts) {
            throw ValidationException::withMessages([
                'starts_at' => 'El odontólogo ya tiene una cita en ese horario.',
            ]);
        }
    }

    private function assertValidFutureInterval(CarbonImmutable $startsAt, CarbonImmutable $endsAt): void
    {
        if ($startsAt->lessThanOrEqualTo(now('UTC'))) {
            throw ValidationException::withMessages([
                'starts_at' => 'La cita debe programarse en una fecha y hora futuras.',
            ]);
        }

        if ($endsAt->lessThanOrEqualTo($startsAt)) {
            throw ValidationException::withMessages([
                'ends_at' => 'La hora de finalización debe ser posterior al inicio.',
            ]);
        }
    }

    private function parseLocalDateTime(string $value): CarbonImmutable
    {
        $dateTime = CarbonImmutable::createFromFormat('!Y-m-d\\TH:i', $value, config('appointments.timezone'));

        if (! $dateTime instanceof CarbonImmutable) {
            throw ValidationException::withMessages([
                'starts_at' => 'Ingresa una fecha y hora válidas.',
            ]);
        }

        return $dateTime->utc();
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    private function recordEvent(
        Appointment $appointment,
        User $actor,
        string $eventType,
        ?AppointmentStatus $fromStatus,
        ?AppointmentStatus $toStatus,
        array $changes,
        ?string $reason = null,
    ): void {
        $appointment->events()->create([
            'user_id' => $actor->id,
            'event_type' => $eventType,
            'from_status' => $fromStatus?->value,
            'to_status' => $toStatus?->value,
            'changes' => $changes === [] ? null : $changes,
            'reason' => $reason,
        ]);
    }

    private function rethrowUnlessOverlapViolation(QueryException $exception): never
    {
        $sqlState = $exception->errorInfo[0] ?? (string) $exception->getCode();

        if ($sqlState === '23P01') {
            throw ValidationException::withMessages([
                'starts_at' => 'El odontólogo ya tiene una cita en ese horario.',
            ]);
        }

        throw $exception;
    }
}
