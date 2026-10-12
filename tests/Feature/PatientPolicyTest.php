<?php

namespace Tests\Feature;

use App\Models\Dentist;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Policies\PatientPolicy;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PatientPolicyTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[DataProvider('rolesAllowedToListPatients')]
    public function test_allowed_roles_can_view_the_patient_list(string $roleName, bool $expected): void
    {
        $user = $this->userWithRole($roleName);

        $this->assertSame($expected, (new PatientPolicy)->viewAny($user));
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function rolesAllowedToListPatients(): array
    {
        return [
            'administrator' => [Role::ADMINISTRATOR, true],
            'dentist' => [Role::DENTIST, true],
            'receptionist' => [Role::RECEPTIONIST, true],
            'legacy role' => [Role::SALES, false],
        ];
    }

    public function test_dentist_can_view_only_active_patients_assigned_to_an_active_profile(): void
    {
        $user = $this->userWithRole(Role::DENTIST);
        $dentist = Dentist::factory()->for($user, 'user')->create();
        $assignedPatient = Patient::factory()->create();
        $unassignedPatient = Patient::factory()->create();
        $dentist->patients()->attach($assignedPatient);
        $policy = new PatientPolicy;

        $this->assertTrue($policy->view($user, $assignedPatient));
        $this->assertFalse($policy->view($user, $unassignedPatient));

        $dentist->update(['is_active' => false]);

        $this->assertFalse($policy->view($user, $assignedPatient));
    }

    public function test_clinical_history_requires_assigned_dentist_or_explicit_administrator_grant(): void
    {
        $patient = Patient::factory()->create();
        $dentistUser = $this->userWithRole(Role::DENTIST);
        $dentist = Dentist::factory()->for($dentistUser, 'user')->create();
        $patient->dentists()->attach($dentist);
        $administrator = $this->userWithRole(Role::ADMINISTRATOR);
        $receptionist = $this->userWithRole(Role::RECEPTIONIST);
        $policy = new PatientPolicy;

        $this->assertTrue($policy->viewClinicalHistory($dentistUser, $patient));
        $this->assertFalse($policy->viewClinicalHistory($receptionist, $patient));
        $this->assertFalse($policy->viewClinicalHistory($administrator, $patient));

        $administrator->update(['can_view_clinical_records' => true]);
        $this->assertTrue($policy->viewClinicalHistory($administrator, $patient));

        $dentist->update(['is_active' => false]);
        $this->assertFalse($policy->viewClinicalHistory($dentistUser, $patient));
    }

    #[DataProvider('rolesAllowedToManagePatients')]
    public function test_only_administrators_and_receptionists_can_manage_patient_records(
        string $roleName,
        bool $expected,
    ): void {
        $user = $this->userWithRole($roleName);
        $patient = Patient::factory()->create();
        $policy = new PatientPolicy;

        $this->assertSame($expected, $policy->create($user));
        $this->assertSame($expected, $policy->update($user, $patient));
        $this->assertSame($expected, $policy->delete($user, $patient));
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function rolesAllowedToManagePatients(): array
    {
        return [
            'administrator' => [Role::ADMINISTRATOR, true],
            'receptionist' => [Role::RECEPTIONIST, true],
            'dentist' => [Role::DENTIST, false],
            'legacy role' => [Role::SALES, false],
        ];
    }

    #[DataProvider('rolesAllowedToAssignDentists')]
    public function test_only_administrators_can_assign_dentists(string $roleName, bool $expected): void
    {
        $user = $this->userWithRole($roleName);

        $this->assertSame($expected, (new PatientPolicy)->assignDentists($user));
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function rolesAllowedToAssignDentists(): array
    {
        return [
            'administrator' => [Role::ADMINISTRATOR, true],
            'receptionist' => [Role::RECEPTIONIST, false],
            'dentist' => [Role::DENTIST, false],
        ];
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::factory()->create(['name' => $roleName]);

        return User::factory()->for($role)->create(['is_active' => true]);
    }
}
