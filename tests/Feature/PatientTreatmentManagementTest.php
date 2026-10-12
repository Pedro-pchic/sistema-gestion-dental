<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\TreatmentPlanStatus;
use App\Models\Appointment;
use App\Models\Dentist;
use App\Models\Patient;
use App\Models\PatientTreatment;
use App\Models\Role;
use App\Models\Treatment;
use App\Models\TreatmentPlan;
use App\Models\TreatmentPlanItem;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class PatientTreatmentManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_assigned_dentist_records_procedure_with_historical_price_and_audit(): void
    {
        [$dentistUser, $dentist, $patient, $appointment] = $this->assignedContext();
        $treatment = Treatment::factory()->create(['name' => 'Resina', 'price' => '350.00']);
        $payload = $this->procedurePayload($treatment, $appointment) + [
            'patient_id' => 9999,
            'dentist_id' => 9999,
            'registered_by' => 9999,
            'price_applied' => '0.01',
        ];

        $this->actingAs($dentistUser)
            ->post(route('pacientes.tratamientos.store', $patient), $payload)
            ->assertRedirectToRoute('pacientes.historial-clinico', $patient);

        $procedure = PatientTreatment::query()->firstOrFail();
        $this->assertDatabaseHas('patient_treatments', [
            'id' => $procedure->id,
            'patient_id' => $patient->id,
            'dentist_id' => $dentist->id,
            'treatment_id' => $treatment->id,
            'appointment_id' => $appointment->id,
            'registered_by' => $dentistUser->id,
            'price_applied' => '350.00',
        ]);
        $this->assertDatabaseHas('patient_treatment_events', [
            'patient_treatment_id' => $procedure->id,
            'user_id' => $dentistUser->id,
            'event_type' => 'created',
        ]);

        $treatment->update(['price' => '500.00']);
        $this->assertSame('350.00', $procedure->fresh()->price_applied);
        $this->assertSame(AppointmentStatus::InTreatment, $appointment->fresh()->status);

        $this->actingAs($dentistUser)
            ->get(route('pacientes.historial-clinico', $patient))
            ->assertOk()
            ->assertSeeText('Sin complicaciones durante el procedimiento.')
            ->assertSeeText('350.00');
    }

    public function test_duplicate_procedure_for_the_same_appointment_is_rejected(): void
    {
        [$dentistUser, , $patient, $appointment] = $this->assignedContext();
        $treatment = Treatment::factory()->create();
        $payload = $this->procedurePayload($treatment, $appointment);

        $this->actingAs($dentistUser)
            ->post(route('pacientes.tratamientos.store', $patient), $payload)
            ->assertRedirect();

        $this->actingAs($dentistUser)
            ->post(route('pacientes.tratamientos.store', $patient), $payload)
            ->assertSessionHasErrors('treatment_id');

        $this->assertDatabaseCount('patient_treatments', 1);
    }

    public function test_procedure_rejects_a_cita_belonging_to_another_patient(): void
    {
        [$dentistUser, $dentist, $patient] = $this->assignedContext();
        $otherPatient = Patient::factory()->create();
        $otherAppointment = Appointment::factory()
            ->for($otherPatient)
            ->for($dentist)
            ->create(['status' => AppointmentStatus::Completed]);
        $treatment = Treatment::factory()->create();

        $this->actingAs($dentistUser)
            ->post(route('pacientes.tratamientos.store', $patient), $this->procedurePayload($treatment, $otherAppointment))
            ->assertSessionHasErrors([
                'appointment_id' => 'La cita debe pertenecer al paciente y al odontólogo responsable.',
            ]);

        $this->assertDatabaseCount('patient_treatments', 0);
    }

    public function test_procedure_requires_an_appointment_when_clinic_rules_do_not_allow_unlinked_records(): void
    {
        [$dentistUser, , $patient] = $this->assignedContext();
        $treatment = Treatment::factory()->create();
        $payload = $this->procedurePayload($treatment, null);
        unset($payload['appointment_id']);

        $this->actingAs($dentistUser)
            ->post(route('pacientes.tratamientos.store', $patient), $payload)
            ->assertSessionHasErrors('appointment_id');

        $this->assertDatabaseCount('patient_treatments', 0);
    }

    public function test_recording_a_planned_procedure_completes_its_item_and_plan(): void
    {
        [$dentistUser, $dentist, $patient, $appointment] = $this->assignedContext();
        $treatment = Treatment::factory()->create();
        $plan = TreatmentPlan::factory()->for($patient)->for($dentist)->create([
            'status' => TreatmentPlanStatus::Pending,
        ]);
        $planItem = TreatmentPlanItem::factory()->for($plan, 'plan')->for($treatment)->create([
            'status' => TreatmentPlanStatus::Pending,
        ]);

        $this->actingAs($dentistUser)
            ->post(route('pacientes.tratamientos.store', $patient), $this->procedurePayload($treatment, $appointment, $planItem))
            ->assertRedirectToRoute('pacientes.historial-clinico', $patient);

        $this->assertSame(TreatmentPlanStatus::Completed, $planItem->fresh()->status);
        $this->assertSame(TreatmentPlanStatus::Completed, $plan->fresh()->status);
        $this->assertDatabaseHas('patient_treatments', [
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'treatment_plan_item_id' => $planItem->id,
        ]);
    }

    public function test_planned_item_quantity_requires_each_procedure_before_completion(): void
    {
        [$dentistUser, $dentist, $patient, $firstAppointment] = $this->assignedContext();
        $treatment = Treatment::factory()->create();
        $plan = TreatmentPlan::factory()->for($patient)->for($dentist)->create();
        $planItem = TreatmentPlanItem::factory()->for($plan, 'plan')->for($treatment)->create([
            'quantity' => 2,
            'status' => TreatmentPlanStatus::Pending,
        ]);
        $secondAppointment = Appointment::factory()
            ->for($patient)
            ->for($dentist)
            ->create(['status' => AppointmentStatus::Completed]);

        $this->actingAs($dentistUser)
            ->post(route('pacientes.tratamientos.store', $patient), $this->procedurePayload($treatment, $firstAppointment, $planItem))
            ->assertRedirect();

        $this->assertSame(TreatmentPlanStatus::InProgress, $planItem->fresh()->status);
        $this->assertSame(TreatmentPlanStatus::InProgress, $plan->fresh()->status);

        $secondProcedure = $this->procedurePayload($treatment, $secondAppointment, $planItem);
        $secondProcedure['performed_at'] = '2030-02-12T11:00';

        $this->actingAs($dentistUser)
            ->post(route('pacientes.tratamientos.store', $patient), $secondProcedure)
            ->assertRedirect();

        $this->assertSame(TreatmentPlanStatus::Completed, $planItem->fresh()->status);
        $this->assertSame(TreatmentPlanStatus::Completed, $plan->fresh()->status);
        $this->assertDatabaseCount('patient_treatments', 2);

        $thirdAppointment = Appointment::factory()
            ->for($patient)
            ->for($dentist)
            ->create(['status' => AppointmentStatus::Completed]);

        $this->actingAs($dentistUser)
            ->post(route('pacientes.tratamientos.store', $patient), $this->procedurePayload($treatment, $thirdAppointment, $planItem))
            ->assertSessionHasErrors('treatment_plan_item_id');

        $this->assertDatabaseCount('patient_treatments', 2);
    }

    public function test_procedure_edits_are_recorded_in_the_audit_history(): void
    {
        [$dentistUser, , $patient, $appointment] = $this->assignedContext();
        $treatment = Treatment::factory()->create();

        $this->actingAs($dentistUser)
            ->post(route('pacientes.tratamientos.store', $patient), $this->procedurePayload($treatment, $appointment))
            ->assertRedirect();

        $procedure = PatientTreatment::query()->firstOrFail();
        $this->actingAs($dentistUser)
            ->put(route('pacientes.tratamientos.update', [$patient, $procedure]), [
                'procedure_description' => 'Se actualizó la descripción del procedimiento.',
                'clinical_notes' => 'Se añadió una observación de seguimiento.',
            ])
            ->assertRedirectToRoute('pacientes.historial-clinico', $patient);

        $this->assertDatabaseHas('patient_treatments', [
            'id' => $procedure->id,
            'updated_by' => $dentistUser->id,
            'clinical_notes' => 'Se añadió una observación de seguimiento.',
        ]);
        $this->assertDatabaseHas('patient_treatment_events', [
            'patient_treatment_id' => $procedure->id,
            'user_id' => $dentistUser->id,
            'event_type' => 'updated',
        ]);
    }

    public function test_another_assigned_dentist_cannot_edit_a_colleague_procedure(): void
    {
        [$dentistUser, , $patient, $appointment] = $this->assignedContext();
        $treatment = Treatment::factory()->create();

        $this->actingAs($dentistUser)
            ->post(route('pacientes.tratamientos.store', $patient), $this->procedurePayload($treatment, $appointment))
            ->assertRedirect();

        $procedure = PatientTreatment::query()->firstOrFail();
        $otherDentistUser = $this->userWithRole(Role::DENTIST);
        $otherDentist = Dentist::factory()->for($otherDentistUser, 'user')->create();
        $patient->dentists()->attach($otherDentist);

        $this->actingAs($otherDentistUser)
            ->put(route('pacientes.tratamientos.update', [$patient, $procedure]), [
                'procedure_description' => 'Cambio no autorizado.',
                'clinical_notes' => 'No debe guardarse.',
            ])
            ->assertForbidden();

        $this->assertSame('Se colocó resina en la pieza dental.', $procedure->fresh()->procedure_description);
    }

    public function test_unlinked_duplicate_procedures_are_rejected_when_clinic_allows_them(): void
    {
        Config::set('dental.allow_treatment_without_appointment', true);
        [$dentistUser, , $patient] = $this->assignedContext();
        $treatment = Treatment::factory()->create();
        $payload = $this->procedurePayload($treatment, null);

        $this->actingAs($dentistUser)
            ->post(route('pacientes.tratamientos.store', $patient), $payload)
            ->assertRedirect();

        $this->actingAs($dentistUser)
            ->post(route('pacientes.tratamientos.store', $patient), $payload)
            ->assertSessionHasErrors('treatment_id');

        $this->assertDatabaseCount('patient_treatments', 1);
    }

    private function procedurePayload(
        Treatment $treatment,
        ?Appointment $appointment,
        ?TreatmentPlanItem $planItem = null,
    ): array {
        return [
            'treatment_id' => $treatment->id,
            'appointment_id' => $appointment?->id,
            'treatment_plan_item_id' => $planItem?->id,
            'performed_at' => '2030-02-12T10:00',
            'procedure_description' => 'Se colocó resina en la pieza dental.',
            'clinical_notes' => 'Sin complicaciones durante el procedimiento.',
        ];
    }

    /** @return array{User, Dentist, Patient, Appointment} */
    private function assignedContext(): array
    {
        $role = Role::factory()->create(['name' => Role::DENTIST]);
        $dentistUser = User::factory()->for($role)->create(['is_active' => true]);
        $dentist = Dentist::factory()->for($dentistUser, 'user')->create();
        $patient = Patient::factory()->create();
        $patient->dentists()->attach($dentist);
        $receptionist = User::factory()->for(Role::factory()->create(['name' => Role::RECEPTIONIST]))->create(['is_active' => true]);
        $appointment = Appointment::factory()
            ->for($patient)
            ->for($dentist)
            ->for($receptionist, 'creator')
            ->create(['status' => AppointmentStatus::InTreatment]);

        return [$dentistUser, $dentist, $patient, $appointment];
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::query()->firstOrCreate(['name' => $roleName]);

        return User::factory()->for($role)->create(['is_active' => true]);
    }
}
