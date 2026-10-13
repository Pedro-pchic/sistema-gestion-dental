<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Dentist;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AppointmentManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_to_the_appointment_agenda_login(): void
    {
        $this->get(route('citas.index'))
            ->assertRedirectToRoute('login');
    }

    public function test_receptionist_can_create_and_view_an_appointment_with_audit_history(): void
    {
        $receptionist = $this->userWithRole(Role::RECEPTIONIST);
        [$dentistUser, $dentist, $patient] = $this->assignedPatientAndDentist();

        $response = $this->actingAs($receptionist)
            ->post(route('citas.store'), $this->appointmentPayload($patient, $dentist));

        $appointment = Appointment::query()->firstOrFail();
        $response->assertRedirectToRoute('citas.show', $appointment);
        $this->assertSame('2030-02-12 15:00:00', $appointment->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame(AppointmentStatus::Scheduled, $appointment->status);
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'patient_id' => $patient->id,
            'dentist_id' => $dentist->id,
            'created_by' => $receptionist->id,
            'status' => AppointmentStatus::Scheduled->value,
        ]);
        $this->assertDatabaseHas('appointment_events', [
            'appointment_id' => $appointment->id,
            'user_id' => $receptionist->id,
            'event_type' => 'created',
            'to_status' => AppointmentStatus::Scheduled->value,
        ]);

        $this->actingAs($dentistUser)
            ->get(route('citas.show', $appointment))
            ->assertOk()
            ->assertSeeText($patient->first_name)
            ->assertSeeText('Consulta de evaluación');
    }

    public function test_receptionist_cannot_book_an_unassigned_dentist_for_a_patient(): void
    {
        $receptionist = $this->userWithRole(Role::RECEPTIONIST);
        $dentistUser = $this->userWithRole(Role::DENTIST);
        $dentist = Dentist::factory()->for($dentistUser, 'user')->create();
        $patient = Patient::factory()->create();

        $this->actingAs($receptionist)
            ->post(route('citas.store'), $this->appointmentPayload($patient, $dentist))
            ->assertSessionHasErrors([
                'dentist_id' => 'El odontólogo debe estar asignado previamente a este paciente.',
            ]);

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_creation_rejects_inactive_records_past_dates_and_invalid_intervals(): void
    {
        $receptionist = $this->userWithRole(Role::RECEPTIONIST);
        [, $dentist, $patient] = $this->assignedPatientAndDentist();

        $patient->update(['is_active' => false]);
        $this->actingAs($receptionist)
            ->post(route('citas.store'), $this->appointmentPayload($patient, $dentist))
            ->assertSessionHasErrors('patient_id');
        $patient->update(['is_active' => true]);

        $dentist->update(['is_active' => false]);
        $this->post(route('citas.store'), $this->appointmentPayload($patient, $dentist))
            ->assertSessionHasErrors('dentist_id');
        $dentist->update(['is_active' => true]);

        $this->post(route('citas.store'), $this->appointmentPayload(
            $patient,
            $dentist,
            '2020-02-12T09:00',
            '2020-02-12T10:00',
        ))->assertSessionHasErrors('starts_at');

        $this->post(route('citas.store'), $this->appointmentPayload(
            $patient,
            $dentist,
            '2030-02-12T10:00',
            '2030-02-12T09:00',
        ))->assertSessionHasErrors('ends_at');

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_reservation_attempts_cannot_overlap_for_the_same_dentist_but_can_be_adjacent(): void
    {
        $receptionist = $this->userWithRole(Role::RECEPTIONIST);
        [, $dentist, $patient] = $this->assignedPatientAndDentist();
        $firstPayload = $this->appointmentPayload($patient, $dentist);

        $this->actingAs($receptionist)
            ->post(route('citas.store'), $firstPayload)
            ->assertRedirect();

        $overlapPayload = $this->appointmentPayload($patient, $dentist, '2030-02-12T09:30', '2030-02-12T10:30');
        $this->actingAs($receptionist)
            ->post(route('citas.store'), $overlapPayload)
            ->assertSessionHasErrors([
                'starts_at' => 'El odontólogo ya tiene una cita en ese horario.',
            ]);

        $adjacentPayload = $this->appointmentPayload($patient, $dentist, '2030-02-12T10:00', '2030-02-12T11:00');
        $this->actingAs($receptionist)
            ->post(route('citas.store'), $adjacentPayload)
            ->assertRedirect();

        $this->assertDatabaseCount('appointments', 2);
    }

    public function test_two_dentists_can_have_appointments_at_the_same_time(): void
    {
        $receptionist = $this->userWithRole(Role::RECEPTIONIST);
        [, $firstDentist, $patient] = $this->assignedPatientAndDentist();
        $secondDentistUser = $this->userWithRole(Role::DENTIST);
        $secondDentist = Dentist::factory()->for($secondDentistUser, 'user')->create();
        $patient->dentists()->attach($secondDentist);

        $this->actingAs($receptionist)
            ->post(route('citas.store'), $this->appointmentPayload($patient, $firstDentist))
            ->assertRedirect();

        $this->actingAs($receptionist)
            ->post(route('citas.store'), $this->appointmentPayload($patient, $secondDentist))
            ->assertRedirect();

        $this->assertDatabaseCount('appointments', 2);
        $this->assertSame(
            '2030-02-12 15:00:00',
            Appointment::query()->where('dentist_id', $firstDentist->id)->firstOrFail()->starts_at->format('Y-m-d H:i:s'),
        );
        $this->assertSame(
            '2030-02-12 15:00:00',
            Appointment::query()->where('dentist_id', $secondDentist->id)->firstOrFail()->starts_at->format('Y-m-d H:i:s'),
        );
    }

    public function test_reprogramming_rejects_conflicts_and_records_a_successful_change(): void
    {
        $receptionist = $this->userWithRole(Role::RECEPTIONIST);
        [, $dentist, $patient] = $this->assignedPatientAndDentist();
        $first = $this->createAppointment($receptionist, $patient, $dentist);
        $second = $this->createAppointment($receptionist, $patient, $dentist, '2030-02-12T11:00', '2030-02-12T12:00');

        $this->actingAs($receptionist)
            ->put(route('citas.update', $second), [
                'starts_at' => '2030-02-12T09:30',
                'ends_at' => '2030-02-12T10:30',
            ])
            ->assertSessionHasErrors([
                'starts_at' => 'El odontólogo ya tiene una cita en ese horario.',
            ]);

        $this->assertSame('2030-02-12 17:00:00', $second->fresh()->starts_at->format('Y-m-d H:i:s'));

        $this->actingAs($receptionist)
            ->put(route('citas.update', $second), [
                'starts_at' => '2030-02-12T10:00',
                'ends_at' => '2030-02-12T11:00',
            ])
            ->assertRedirectToRoute('citas.show', $second);

        $this->assertSame('2030-02-12 16:00:00', $second->fresh()->starts_at->format('Y-m-d H:i:s'));
        $this->assertDatabaseHas('appointment_events', [
            'appointment_id' => $second->id,
            'event_type' => 'rescheduled',
            'from_status' => AppointmentStatus::Scheduled->value,
            'to_status' => AppointmentStatus::Scheduled->value,
        ]);
        $this->assertSame(1, $first->events()->count());
    }

    public function test_cancel_requires_a_reason_and_prevents_later_state_changes(): void
    {
        $receptionist = $this->userWithRole(Role::RECEPTIONIST);
        $administrator = $this->userWithRole(Role::ADMINISTRATOR);
        [, $dentist, $patient] = $this->assignedPatientAndDentist();
        $appointment = $this->createAppointment($receptionist, $patient, $dentist);

        $this->actingAs($receptionist)
            ->post(route('citas.cancel', $appointment), [])
            ->assertSessionHasErrors(['reason' => 'Indica el motivo de la cancelación.']);

        $this->assertSame(AppointmentStatus::Scheduled, $appointment->fresh()->status);

        $this->actingAs($receptionist)
            ->post(route('citas.cancel', $appointment), ['reason' => 'El paciente solicitó otra fecha.'])
            ->assertRedirect();

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => AppointmentStatus::Cancelled->value,
            'cancelled_by' => $receptionist->id,
            'cancellation_reason' => 'El paciente solicitó otra fecha.',
        ]);
        $this->assertDatabaseHas('appointment_events', [
            'appointment_id' => $appointment->id,
            'event_type' => 'cancelled',
            'reason' => 'El paciente solicitó otra fecha.',
        ]);

        $this->actingAs($administrator)
            ->post(route('citas.complete', $appointment))
            ->assertForbidden();

        $this->assertSame(AppointmentStatus::Cancelled, $appointment->fresh()->status);
    }

    public function test_confirmed_appointment_can_be_started_and_completed_by_its_dentist(): void
    {
        $receptionist = $this->userWithRole(Role::RECEPTIONIST);
        [$dentistUser, $dentist, $patient] = $this->assignedPatientAndDentist();
        $appointment = $this->createAppointment($receptionist, $patient, $dentist);

        $this->actingAs($receptionist)
            ->post(route('citas.confirm', $appointment))
            ->assertRedirect();
        $this->assertSame(AppointmentStatus::Confirmed, $appointment->fresh()->status);

        $this->actingAs($dentistUser)
            ->post(route('citas.start', $appointment))
            ->assertRedirect();
        $this->assertSame(AppointmentStatus::InTreatment, $appointment->fresh()->status);

        $this->actingAs($dentistUser)
            ->post(route('citas.complete', $appointment))
            ->assertRedirect();
        $this->assertSame(AppointmentStatus::Completed, $appointment->fresh()->status);
        $this->assertDatabaseCount('appointment_events', 4);
    }

    public function test_no_show_is_blocked_before_the_appointment_and_allowed_after_its_start(): void
    {
        $receptionist = $this->userWithRole(Role::RECEPTIONIST);
        [, $dentist, $patient] = $this->assignedPatientAndDentist();
        $appointment = $this->createAppointment($receptionist, $patient, $dentist);
        $this->travelTo(CarbonImmutable::create(2030, 2, 12, 8, 0, 0, 'America/Guatemala'));

        $this->actingAs($receptionist)
            ->post(route('citas.no-show', $appointment))
            ->assertForbidden();

        $this->travelTo(CarbonImmutable::create(2030, 2, 12, 10, 0, 0, 'America/Guatemala'));
        $this->actingAs($receptionist)
            ->post(route('citas.no-show', $appointment))
            ->assertRedirect();

        $this->assertSame(AppointmentStatus::NoShow, $appointment->fresh()->status);
    }

    public function test_agenda_supports_day_week_month_views_and_patient_search(): void
    {
        $receptionist = $this->userWithRole(Role::RECEPTIONIST);
        [, $dentist, $patient] = $this->assignedPatientAndDentist();
        $patient->update(['first_name' => 'Marco']);
        $appointment = $this->createAppointment($receptionist, $patient, $dentist);

        foreach (['day', 'week', 'month'] as $view) {
            $this->actingAs($receptionist)
                ->get(route('citas.index', [
                    'view' => $view,
                    'date' => '2030-02-12',
                    'search' => 'Marco',
                ]))
                ->assertOk()
                ->assertSeeText('Marco')
                ->assertSee(route('citas.show', $appointment), false);
        }
    }

    public function test_agenda_filters_by_planned_treatment_without_hiding_occupied_slots(): void
    {
        $receptionist = $this->userWithRole(Role::RECEPTIONIST);
        [, $dentist, $patient] = $this->assignedPatientAndDentist();
        $first = $this->createAppointment($receptionist, $patient, $dentist);
        $second = $this->createAppointment($receptionist, $patient, $dentist, '2030-02-12T11:00', '2030-02-12T12:00');
        $second->update(['planned_treatment' => 'Limpieza dental']);

        $this->actingAs($receptionist)
            ->get(route('citas.index', [
                'view' => 'day',
                'date' => '2030-02-12',
                'dentist_id' => $dentist->id,
                'treatment' => 'limpieza',
            ]))
            ->assertOk()
            ->assertSee(route('citas.show', $second), false)
            ->assertDontSee(route('citas.show', $first), false)
            ->assertViewHas('occupiedAppointments', fn ($items): bool => $items->count() === 2)
            ->assertViewHas('freeIntervals', fn (array $intervals): bool => count($intervals) === 1
                && $intervals[0]['starts_at']->setTimezone('America/Guatemala')->format('H:i') === '10:00'
                && $intervals[0]['ends_at']->setTimezone('America/Guatemala')->format('H:i') === '11:00');
    }

    public function test_availability_includes_overnight_reservations_and_excludes_cancelled_ones(): void
    {
        $receptionist = $this->userWithRole(Role::RECEPTIONIST);
        [, $dentist, $patient] = $this->assignedPatientAndDentist();
        $this->createAppointment($receptionist, $patient, $dentist, '2030-02-11T23:30', '2030-02-12T00:30');
        $this->createAppointment($receptionist, $patient, $dentist, '2030-02-12T01:00', '2030-02-12T02:00');
        $cancelled = $this->createAppointment($receptionist, $patient, $dentist, '2030-02-12T00:30', '2030-02-12T01:00');

        $this->actingAs($receptionist)
            ->post(route('citas.cancel', $cancelled), ['reason' => 'Cambio solicitado por el paciente.'])
            ->assertRedirect();

        $this->get(route('citas.index', [
            'view' => 'day',
            'date' => '2030-02-12',
            'dentist_id' => $dentist->id,
        ]))
            ->assertOk()
            ->assertViewHas('occupiedAppointments', fn ($items): bool => $items->count() === 2)
            ->assertViewHas('freeIntervals', fn (array $intervals): bool => count($intervals) === 1
                && $intervals[0]['starts_at']->setTimezone('America/Guatemala')->format('H:i') === '00:30'
                && $intervals[0]['ends_at']->setTimezone('America/Guatemala')->format('H:i') === '01:00');
    }

    public function test_patient_history_shows_appointments_to_authorized_users(): void
    {
        $receptionist = $this->userWithRole(Role::RECEPTIONIST);
        [, $dentist, $patient] = $this->assignedPatientAndDentist();
        $this->createAppointment($receptionist, $patient, $dentist);

        $this->actingAs($receptionist)
            ->get(route('pacientes.citas.index', $patient))
            ->assertOk()
            ->assertSeeText('Consulta de evaluación');
    }

    public function test_dentist_cannot_view_another_dentists_appointment_or_agenda_entries(): void
    {
        [$dentistUser, $dentist, $patient] = $this->assignedPatientAndDentist();
        $patient->update(['first_name' => 'Paciente', 'last_name' => 'Visible']);

        $otherDentistUser = $this->userWithRole(Role::DENTIST);
        $otherDentist = Dentist::factory()->for($otherDentistUser, 'user')->create();
        $otherPatient = Patient::factory()->create([
            'first_name' => 'Paciente',
            'last_name' => 'Privado',
        ]);
        $otherDentist->patients()->attach($otherPatient);
        $receptionist = $this->userWithRole(Role::RECEPTIONIST);
        $ownAppointment = $this->createAppointment($receptionist, $patient, $dentist);
        $otherAppointment = $this->createAppointment($receptionist, $otherPatient, $otherDentist);

        $this->actingAs($dentistUser)
            ->get(route('citas.show', $otherAppointment))
            ->assertForbidden();

        $this->get(route('citas.index', ['view' => 'day', 'date' => '2030-02-12']))
            ->assertOk()
            ->assertSee(route('citas.show', $ownAppointment), false)
            ->assertDontSee(route('citas.show', $otherAppointment), false)
            ->assertSeeText('Visible')
            ->assertDontSeeText('Privado')
            ->assertViewHas('occupiedAppointments', fn ($items): bool => $items->count() === 1
                && $items->first()->id === $ownAppointment->id);

        $this->get(route('citas.index', ['view' => 'day', 'date' => '2030-02-12', 'dentist_id' => $otherDentist->id]))
            ->assertSessionHasErrors('dentist_id');
    }

    public function test_changing_a_dentists_role_preserves_appointment_history_and_disables_the_profile(): void
    {
        $administrator = $this->userWithRole(Role::ADMINISTRATOR);
        $receptionist = $this->userWithRole(Role::RECEPTIONIST);
        [$dentistUser, $dentist, $patient] = $this->assignedPatientAndDentist();
        $appointment = $this->createAppointment($receptionist, $patient, $dentist);
        $newRole = Role::query()->firstOrCreate(['name' => Role::RECEPTIONIST]);

        $this->actingAs($administrator)
            ->put(route('usuarios.update', $dentistUser), [
                'name' => $dentistUser->name,
                'email' => $dentistUser->email,
                'role_id' => $newRole->id,
                'is_active' => true,
            ])
            ->assertRedirectToRoute('usuarios.index');

        $this->assertDatabaseHas('appointments', ['id' => $appointment->id]);
        $this->assertDatabaseHas('dentists', ['id' => $dentist->id, 'is_active' => false]);
        $this->assertDatabaseMissing('dentist_patient', [
            'dentist_id' => $dentist->id,
            'patient_id' => $patient->id,
        ]);
    }

    public function test_audit_history_prevents_hard_deleting_an_appointment(): void
    {
        $receptionist = $this->userWithRole(Role::RECEPTIONIST);
        [, $dentist, $patient] = $this->assignedPatientAndDentist();
        $appointment = $this->createAppointment($receptionist, $patient, $dentist);

        $this->expectException(QueryException::class);

        $appointment->delete();
    }

    private function createAppointment(
        User $receptionist,
        Patient $patient,
        Dentist $dentist,
        string $startsAt = '2030-02-12T09:00',
        string $endsAt = '2030-02-12T10:00',
    ): Appointment {
        $this->actingAs($receptionist)
            ->post(route('citas.store'), $this->appointmentPayload($patient, $dentist, $startsAt, $endsAt))
            ->assertRedirect();

        return Appointment::query()->latest('id')->firstOrFail();
    }

    /** @return array<string, int|string> */
    private function appointmentPayload(
        Patient $patient,
        Dentist $dentist,
        string $startsAt = '2030-02-12T09:00',
        string $endsAt = '2030-02-12T10:00',
    ): array {
        return [
            'patient_id' => $patient->id,
            'dentist_id' => $dentist->id,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'planned_treatment' => 'Consulta de evaluación',
            'administrative_notes' => 'Confirmar disponibilidad de recepción.',
        ];
    }

    /** @return array{User, Dentist, Patient} */
    private function assignedPatientAndDentist(): array
    {
        $dentistUser = $this->userWithRole(Role::DENTIST);
        $dentist = Dentist::factory()->for($dentistUser, 'user')->create();
        $patient = Patient::factory()->create();
        $dentist->patients()->attach($patient);

        return [$dentistUser, $dentist, $patient];
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::query()->firstOrCreate(['name' => $roleName]);

        return User::factory()->for($role)->create(['is_active' => true]);
    }
}
