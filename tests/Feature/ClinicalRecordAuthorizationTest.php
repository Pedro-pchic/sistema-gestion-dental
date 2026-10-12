<?php

namespace Tests\Feature;

use App\Models\Dentist;
use App\Models\Patient;
use App\Models\PatientTreatment;
use App\Models\Role;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ClinicalRecordAuthorizationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_receptionist_and_unassigned_dentist_cannot_open_clinical_history(): void
    {
        [$dentistUser, $patient] = $this->assignedPatient();
        $procedure = PatientTreatment::factory()->for($patient)->create([
            'procedure_description' => 'Procedimiento privado.',
            'clinical_notes' => 'Nota clínica privada.',
        ]);
        $receptionist = $this->userWithRole(Role::RECEPTIONIST);

        $this->actingAs($receptionist)
            ->get(route('pacientes.historial-clinico', $patient))
            ->assertForbidden()
            ->assertDontSeeText('Nota clínica privada.');

        $otherDentist = $this->userWithRole(Role::DENTIST);
        Dentist::factory()->for($otherDentist, 'user')->create();

        $this->actingAs($otherDentist)
            ->get(route('pacientes.historial-clinico', $patient))
            ->assertForbidden()
            ->assertDontSeeText('Nota clínica privada.');
    }

    public function test_administrator_needs_an_explicit_clinical_access_grant(): void
    {
        [, $patient] = $this->assignedPatient();
        $administrator = $this->userWithRole(Role::ADMINISTRATOR);

        $this->actingAs($administrator)
            ->get(route('pacientes.historial-clinico', $patient))
            ->assertForbidden();

        $administrator->update(['can_view_clinical_records' => true]);

        $this->actingAs($administrator)
            ->get(route('pacientes.historial-clinico', $patient))
            ->assertOk();
    }

    public function test_receptionist_cannot_register_procedures_or_create_treatment_plans(): void
    {
        [, $patient] = $this->assignedPatient();
        $receptionist = $this->userWithRole(Role::RECEPTIONIST);
        $treatment = Treatment::factory()->create();

        $this->actingAs($receptionist)
            ->get(route('pacientes.planes.create', $patient))
            ->assertForbidden();

        $this->actingAs($receptionist)
            ->post(route('pacientes.tratamientos.store', $patient), [
                'treatment_id' => $treatment->id,
                'appointment_id' => 1,
            ])
            ->assertForbidden();

        $this->actingAs($receptionist)
            ->post(route('pacientes.planes.store', $patient), [])
            ->assertForbidden();
    }

    public function test_user_administration_can_grant_clinical_access_only_to_an_administrator(): void
    {
        $administrator = $this->userWithRole(Role::ADMINISTRATOR);
        $targetAdministrator = $this->userWithRole(Role::ADMINISTRATOR);
        $dentist = $this->userWithRole(Role::DENTIST);

        $this->actingAs($administrator)
            ->put(route('usuarios.update', $targetAdministrator), [
                'name' => $targetAdministrator->name,
                'email' => $targetAdministrator->email,
                'role_id' => $targetAdministrator->role_id,
                'password' => null,
                'password_confirmation' => null,
                'is_active' => true,
                'can_view_clinical_records' => true,
            ])
            ->assertRedirectToRoute('usuarios.index');

        $this->actingAs($administrator)
            ->put(route('usuarios.update', $dentist), [
                'name' => $dentist->name,
                'email' => $dentist->email,
                'role_id' => $dentist->role_id,
                'password' => null,
                'password_confirmation' => null,
                'is_active' => true,
                'can_view_clinical_records' => true,
            ])
            ->assertRedirectToRoute('usuarios.index');

        $this->assertTrue($targetAdministrator->fresh()->can_view_clinical_records);
        $this->assertFalse($dentist->fresh()->can_view_clinical_records);
    }

    public function test_receptionist_cannot_grant_clinical_access_to_an_administrator(): void
    {
        $receptionist = $this->userWithRole(Role::RECEPTIONIST);
        $targetAdministrator = $this->userWithRole(Role::ADMINISTRATOR);

        $this->actingAs($receptionist)
            ->put(route('usuarios.update', $targetAdministrator), [
                'name' => $targetAdministrator->name,
                'email' => $targetAdministrator->email,
                'role_id' => $targetAdministrator->role_id,
                'password' => null,
                'password_confirmation' => null,
                'is_active' => true,
                'can_view_clinical_records' => true,
            ])
            ->assertForbidden();

        $this->assertFalse($targetAdministrator->fresh()->can_view_clinical_records);
    }

    private function assignedPatient(): array
    {
        $dentistUser = $this->userWithRole(Role::DENTIST);
        $dentist = Dentist::factory()->for($dentistUser, 'user')->create();
        $patient = Patient::factory()->create();
        $patient->dentists()->attach($dentist);

        return [$dentistUser, $patient];
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::query()->firstOrCreate(['name' => $roleName]);

        return User::factory()->for($role)->create(['is_active' => true]);
    }
}
