<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\TreatmentPlanStatus;
use App\Models\Appointment;
use App\Models\Dentist;
use App\Models\Patient;
use App\Models\PatientTreatment;
use App\Models\PatientTreatmentEvent;
use App\Models\Role;
use App\Models\Treatment;
use App\Models\TreatmentPlan;
use App\Models\TreatmentPlanItem;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PatientClinicalHistoryTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_clinical_history_is_hidden_from_guests_receptionists_and_ungranted_administrators(): void
    {
        $patient = Patient::factory()->create();
        $receptionist = $this->userWithRole(Role::RECEPTIONIST);
        $administrator = $this->userWithRole(Role::ADMINISTRATOR);
        $secret = 'Nota clínica reservada';
        PatientTreatment::factory()->for($patient)->create(['clinical_notes' => $secret]);

        $this->get(route('pacientes.historial-clinico', $patient))->assertRedirectToRoute('login');
        $this->actingAs($receptionist)
            ->get(route('pacientes.show', $patient))
            ->assertOk()
            ->assertDontSeeText('Resumen clínico')
            ->assertDontSeeText($secret);
        $this->get(route('pacientes.historial-clinico', $patient))->assertForbidden();
        $this->actingAs($administrator)
            ->get(route('pacientes.historial-clinico', $patient))
            ->assertForbidden();
        $this->actingAs($this->userWithRole(Role::DENTIST))
            ->get(route('pacientes.historial-clinico', $patient))
            ->assertForbidden();

        $administrator->update(['can_view_clinical_records' => true]);

        $this->actingAs($administrator)
            ->get(route('pacientes.historial-clinico', $patient))
            ->assertOk()
            ->assertSeeText($secret);
    }

    public function test_integral_history_orders_existing_records_and_shows_plan_progress_without_financial_amounts(): void
    {
        [$dentistUser, $dentist, $patient] = $this->assignedContext();
        $treatment = Treatment::factory()->create(['name' => 'Resina de prueba']);
        $appointment = Appointment::factory()->for($patient)->for($dentist)->create([
            'status' => AppointmentStatus::Completed,
            'starts_at' => CarbonImmutable::parse('2026-09-01 09:00:00', 'UTC'),
            'ends_at' => CarbonImmutable::parse('2026-09-01 10:00:00', 'UTC'),
        ]);
        Appointment::factory()->for($patient)->for($dentist)->create([
            'status' => AppointmentStatus::Cancelled,
            'starts_at' => CarbonImmutable::parse('2026-08-31 09:00:00', 'UTC'),
            'ends_at' => CarbonImmutable::parse('2026-08-31 10:00:00', 'UTC'),
        ]);
        $procedure = PatientTreatment::factory()->for($patient)->for($dentist)->for($treatment)->create([
            'appointment_id' => $appointment->id,
            'performed_at' => CarbonImmutable::parse('2026-09-02 09:00:00', 'UTC'),
            'procedure_description' => '<script>alert(1)</script> Procedimiento realizado.',
            'clinical_notes' => 'Observación autorizada.',
            'price_applied' => '9876.54',
        ]);
        PatientTreatmentEvent::factory()->for($procedure, 'patientTreatment')->for($dentistUser, 'user')->create([
            'created_at' => CarbonImmutable::parse('2026-09-03 09:00:00', 'UTC'),
        ]);
        $plan = TreatmentPlan::factory()->for($patient)->for($dentist)->create(['title' => 'Plan restaurador']);
        TreatmentPlanItem::factory()->for($plan, 'plan')->for($treatment)->create([
            'status' => TreatmentPlanStatus::Completed,
        ]);

        for ($index = 0; $index < 5; $index++) {
            TreatmentPlanItem::factory()->for($plan, 'plan')->for(Treatment::factory())->create([
                'sort_order' => $index + 1,
            ]);
        }

        $response = $this->actingAs($dentistUser)
            ->get(route('pacientes.historial-clinico', $patient));

        $response->assertOk()
            ->assertSeeText('Plan restaurador')
            ->assertSeeText('1/6 partidas completadas')
            ->assertSeeText('+1 más en el plan')
            ->assertSeeText('Observación autorizada.')
            ->assertSeeText('Actualización clínica')
            ->assertSeeText('Resina de prueba')
            ->assertDontSee('9876.54')
            ->assertDontSee('<script>alert(1)</script>', false);
        $this->assertSame(['update', 'procedure', 'appointment', 'appointment'],
            collect($response->viewData('activity')->items())->pluck('record_type')->all());
        $this->assertSame(1, $response->viewData('procedures')->total());
        $this->assertSame(5, $response->viewData('plans')->first()->items->count());
    }

    public function test_timeline_filters_by_type_treatment_date_and_dentist_and_keeps_filters_in_pagination(): void
    {
        [$dentistUser, $dentist, $patient] = $this->assignedContext();
        $treatment = Treatment::factory()->create(['name' => 'Limpieza filtrada']);

        for ($index = 0; $index < 13; $index++) {
            PatientTreatment::factory()->for($patient)->for($dentist)->for($treatment)->create([
                'performed_at' => CarbonImmutable::parse('2026-09-10 09:00:00', 'UTC')->addMinutes($index),
            ]);
        }
        PatientTreatment::factory()->for($patient)->for($dentist)->create([
            'performed_at' => CarbonImmutable::parse('2026-09-10 10:00:00', 'UTC'),
        ]);
        PatientTreatment::factory()->for($patient)->for($dentist)->for($treatment)->create([
            'performed_at' => CarbonImmutable::parse('2026-09-09 09:00:00', 'UTC'),
        ]);
        $otherDentistUser = $this->userWithRole(Role::DENTIST);
        $otherDentist = Dentist::factory()->for($otherDentistUser, 'user')->create();
        $patient->dentists()->attach($otherDentist);
        PatientTreatment::factory()->for($patient)->for($otherDentist)->for($treatment)->create([
            'performed_at' => CarbonImmutable::parse('2026-09-10 11:00:00', 'UTC'),
        ]);
        Appointment::factory()->for($patient)->for($dentist)->create([
            'starts_at' => CarbonImmutable::parse('2026-09-10 12:00:00', 'UTC'),
            'ends_at' => CarbonImmutable::parse('2026-09-10 13:00:00', 'UTC'),
        ]);

        $filters = [
            'record_type' => 'procedure',
            'treatment_id' => $treatment->id,
            'dentist_id' => $dentist->id,
            'date_from' => '2026-09-10',
            'date_to' => '2026-09-10',
        ];
        $response = $this->actingAs($dentistUser)
            ->get(route('pacientes.historial-clinico', ['patient' => $patient] + $filters));

        $response->assertOk()
            ->assertSee('activity=2', false)
            ->assertSee('treatment_id='.$treatment->id, false)
            ->assertViewHas('activity', fn ($activity): bool => $activity->total() === 13 && $activity->count() === 12);

        $this->get(route('pacientes.historial-clinico', ['patient' => $patient] + $filters + ['activity' => 2]))
            ->assertOk()
            ->assertViewHas('activity', fn ($activity): bool => $activity->total() === 13 && $activity->count() === 1);

        $this->get(route('pacientes.historial-clinico', ['patient' => $patient, 'record_type' => 'invalid']))
            ->assertSessionHasErrors('record_type');
    }

    public function test_dentist_sees_only_own_appointments_in_summary_and_history(): void
    {
        [$dentistUser, $dentist, $patient] = $this->assignedContext();
        $otherDentistUser = $this->userWithRole(Role::DENTIST);
        $otherDentist = Dentist::factory()->for($otherDentistUser, 'user')->create();
        $patient->dentists()->attach($otherDentist);
        $ownAppointment = Appointment::factory()->for($patient)->for($dentist)->create(['planned_treatment' => 'Cita propia']);
        $otherAppointment = Appointment::factory()->for($patient)->for($otherDentist)->create(['planned_treatment' => 'Cita ajena']);

        $this->actingAs($dentistUser)
            ->get(route('pacientes.show', $patient))
            ->assertOk()
            ->assertSee(route('citas.show', $ownAppointment), false)
            ->assertDontSee(route('citas.show', $otherAppointment), false)
            ->assertViewHas('patient', fn (Patient $record): bool => $record->appointments_count === 1);
        $this->get(route('pacientes.historial-clinico', $patient))
            ->assertOk()
            ->assertSeeText('Cita propia')
            ->assertDontSeeText('Cita ajena');
    }

    public function test_empty_clinical_history_has_clear_empty_states(): void
    {
        [$dentistUser, , $patient] = $this->assignedContext();

        $this->actingAs($dentistUser)
            ->get(route('pacientes.show', $patient))
            ->assertOk()
            ->assertSeeText('Sin atención completada')
            ->assertSeeText('Sin cita próxima');
        $this->get(route('pacientes.historial-clinico', $patient))
            ->assertOk()
            ->assertSeeText('No hay movimientos clínicos')
            ->assertSeeText('No hay planes de tratamiento registrados.')
            ->assertSeeText('No hay procedimientos realizados');
    }

    public function test_nested_clinical_urls_reject_a_different_patient(): void
    {
        [$dentistUser, $dentist, $patient] = $this->assignedContext();
        $otherPatient = Patient::factory()->create();
        $plan = TreatmentPlan::factory()->for($patient)->for($dentist)->create();
        $planItem = TreatmentPlanItem::factory()->for($plan, 'plan')->create();
        $procedure = PatientTreatment::factory()->for($patient)->for($dentist)->create();

        $this->actingAs($dentistUser)
            ->get(route('pacientes.planes.show', [$otherPatient, $plan]))
            ->assertNotFound();
        $this->get(route('pacientes.tratamientos.edit', [$otherPatient, $procedure]))
            ->assertNotFound();
        $this->patch(route('pacientes.planes.update', [$otherPatient, $plan]), [
            'status' => TreatmentPlanStatus::InProgress->value,
        ])->assertNotFound();
        $this->patch(route('pacientes.planes.items.update', [$otherPatient, $plan, $planItem]), [
            'status' => TreatmentPlanStatus::InProgress->value,
        ])->assertNotFound();
        $this->put(route('pacientes.tratamientos.update', [$otherPatient, $procedure]), [
            'procedure_description' => 'Cambio no autorizado.',
            'clinical_notes' => 'No debe guardarse.',
        ])->assertNotFound();

        $this->assertSame('Procedimiento realizado.', $procedure->fresh()->procedure_description);
        $this->assertSame(TreatmentPlanStatus::Pending, $plan->fresh()->status);
        $this->assertSame(TreatmentPlanStatus::Pending, $planItem->fresh()->status);
    }

    /** @return array{User, Dentist, Patient} */
    private function assignedContext(): array
    {
        $dentistUser = $this->userWithRole(Role::DENTIST);
        $dentist = Dentist::factory()->for($dentistUser, 'user')->create();
        $patient = Patient::factory()->create();
        $patient->dentists()->attach($dentist);

        return [$dentistUser, $dentist, $patient];
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::query()->firstOrCreate(['name' => $roleName]);

        return User::factory()->for($role)->create(['is_active' => true]);
    }
}
