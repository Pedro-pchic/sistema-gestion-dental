<?php

namespace Tests\Feature;

use App\Models\Dentist;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PatientAdministrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_to_login_from_patient_list(): void
    {
        $this->get(route('pacientes.index'))
            ->assertRedirectToRoute('login');
    }

    public function test_receptionist_can_register_search_edit_deactivate_and_soft_delete_patient(): void
    {
        $receptionist = $this->userWithRole(Role::RECEPTIONIST);

        $this->actingAs($receptionist)
            ->post(route('pacientes.store'), [
                'first_name' => 'Ana María',
                'last_name' => 'López Pérez',
                'date_of_birth' => '1990-04-15',
                'phone' => '5555-0410',
                'address' => 'Zona 1, Ciudad de Guatemala',
                'email' => 'ana@example.test',
            ])
            ->assertRedirect();

        $patient = Patient::query()->where('email', 'ana@example.test')->firstOrFail();

        $this->assertDatabaseHas('patients', [
            'id' => $patient->id,
            'first_name' => 'Ana María',
            'last_name' => 'López Pérez',
            'is_active' => true,
        ]);
        $this->assertNotSame('', $patient->record_number);
        $this->assertNotSame($patient->record_number, Patient::factory()->create()->record_number);

        foreach (['Ana', 'López', '5555-0410'] as $search) {
            $this->actingAs($receptionist)
                ->get(route('pacientes.index', ['search' => $search]))
                ->assertSeeText('Ana María López Pérez');
        }

        $this->actingAs($receptionist)
            ->get(route('pacientes.show', $patient))
            ->assertSeeText('ana@example.test')
            ->assertSeeText('Zona 1, Ciudad de Guatemala');

        $this->actingAs($receptionist)
            ->put(route('pacientes.update', $patient), [
                'first_name' => 'Ana',
                'last_name' => 'López',
                'date_of_birth' => '1990-04-15',
                'phone' => '5555-0444',
                'address' => 'Mixco, Guatemala',
                'email' => 'ana@example.test',
                'is_active' => false,
            ])
            ->assertRedirectToRoute('pacientes.show', $patient);

        $this->assertDatabaseHas('patients', [
            'id' => $patient->id,
            'first_name' => 'Ana',
            'phone' => '5555-0444',
            'is_active' => false,
        ]);

        $this->actingAs($receptionist)
            ->patch(route('pacientes.status', $patient))
            ->assertRedirect();

        $this->assertDatabaseHas('patients', [
            'id' => $patient->id,
            'is_active' => true,
        ]);

        $this->actingAs($receptionist)
            ->delete(route('pacientes.destroy', $patient))
            ->assertRedirectToRoute('pacientes.index');

        $this->assertSoftDeleted($patient);
    }

    public function test_patient_registration_requires_names(): void
    {
        $receptionist = $this->userWithRole(Role::RECEPTIONIST);

        $this->actingAs($receptionist)
            ->post(route('pacientes.store'), [])
            ->assertSessionHasErrors([
                'first_name' => 'Los nombres son obligatorios.',
                'last_name' => 'Los apellidos son obligatorios.',
            ]);

        $this->assertDatabaseCount('patients', 0);
    }

    #[DataProvider('invalidPatientFields')]
    public function test_patient_registration_rejects_invalid_contact_and_birth_date(
        string $field,
        string $value,
        string $message,
    ): void {
        $receptionist = $this->userWithRole(Role::RECEPTIONIST);
        $payload = [
            'first_name' => 'Paciente',
            'last_name' => 'Prueba',
            'email' => 'valid@example.test',
            'date_of_birth' => '1990-01-01',
        ];
        $payload[$field] = $value;

        $this->actingAs($receptionist)
            ->post(route('pacientes.store'), $payload)
            ->assertSessionHasErrors([$field => $message]);

        $this->assertDatabaseCount('patients', 0);
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function invalidPatientFields(): array
    {
        return [
            'invalid email' => ['email', 'not-an-email', 'Ingresa un correo electrónico válido.'],
            'future birth date' => ['date_of_birth', '9999-12-31', 'La fecha de nacimiento no puede ser futura.'],
        ];
    }

    public function test_dentist_can_only_list_and_view_assigned_patients(): void
    {
        $dentistUser = $this->userWithRole(Role::DENTIST);
        $dentist = Dentist::factory()->for($dentistUser, 'user')->create();
        $assignedPatient = Patient::factory()->create([
            'first_name' => 'Paciente',
            'last_name' => 'Autorizado',
        ]);
        $unassignedPatient = Patient::factory()->create([
            'first_name' => 'Paciente',
            'last_name' => 'Privado',
        ]);
        $dentist->patients()->attach($assignedPatient);

        $this->actingAs($dentistUser)
            ->get(route('pacientes.index'))
            ->assertSeeText('Paciente Autorizado')
            ->assertDontSeeText('Paciente Privado');

        $this->actingAs($dentistUser)
            ->get(route('pacientes.show', $assignedPatient))
            ->assertOk();

        $this->actingAs($dentistUser)
            ->get(route('pacientes.show', $unassignedPatient))
            ->assertForbidden();

        $this->actingAs($dentistUser)
            ->put(route('pacientes.update', $assignedPatient), [
                'first_name' => 'Intento',
                'last_name' => 'Edición',
                'is_active' => true,
            ])
            ->assertForbidden();
    }

    public function test_receptionist_cannot_assign_dentists_and_admin_can_assign_them(): void
    {
        $receptionist = $this->userWithRole(Role::RECEPTIONIST);
        $dentistUser = $this->userWithRole(Role::DENTIST);
        $dentist = Dentist::factory()->for($dentistUser, 'user')->create();

        $this->actingAs($receptionist)
            ->post(route('pacientes.store'), [
                'first_name' => 'No',
                'last_name' => 'Asignado',
                'dentist_ids' => [$dentist->id],
            ])
            ->assertSessionHasErrors('dentist_ids');

        $this->assertDatabaseCount('patients', 0);

        $administrator = $this->userWithRole(Role::ADMINISTRATOR);
        $this->actingAs($administrator)
            ->post(route('pacientes.store'), [
                'first_name' => 'Paciente',
                'last_name' => 'Asignado',
                'dentist_ids' => [$dentist->id],
            ])
            ->assertRedirect();

        $patient = Patient::query()->where('last_name', 'Asignado')->firstOrFail();

        $this->assertDatabaseHas('dentist_patient', [
            'dentist_id' => $dentist->id,
            'patient_id' => $patient->id,
        ]);

        $this->actingAs($dentistUser)
            ->get(route('pacientes.show', $patient))
            ->assertOk();
    }

    public function test_user_without_a_dental_role_cannot_open_patient_list(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->get(route('pacientes.index'))
            ->assertForbidden();
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::factory()->create(['name' => $roleName]);

        return User::factory()->for($role)->create(['is_active' => true]);
    }
}
