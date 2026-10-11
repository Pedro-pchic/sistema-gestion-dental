<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Dentist;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Policies\AppointmentPolicy;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AppointmentPolicyTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[DataProvider('rolesThatCanViewAppointmentAgenda')]
    public function test_only_dental_roles_can_open_the_appointment_agenda(string $roleName, bool $expected): void
    {
        $user = $this->userWithRole($roleName);

        $this->assertSame($expected, (new AppointmentPolicy)->viewAny($user));
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function rolesThatCanViewAppointmentAgenda(): array
    {
        return [
            'administrator' => [Role::ADMINISTRATOR, true],
            'dentist' => [Role::DENTIST, true],
            'receptionist' => [Role::RECEPTIONIST, true],
            'legacy sales role' => [Role::SALES, false],
        ];
    }

    #[DataProvider('rolesThatCanManageAppointments')]
    public function test_only_administrators_and_receptionists_can_manage_the_schedule(
        string $roleName,
        bool $expected,
    ): void {
        $user = $this->userWithRole($roleName);
        $appointment = Appointment::factory()->create();
        $policy = new AppointmentPolicy;

        $this->assertSame($expected, $policy->create($user));
        $this->assertSame($expected, $policy->reschedule($user, $appointment));
        $this->assertSame($expected, $policy->confirm($user, $appointment));
        $this->assertSame($expected, $policy->cancel($user, $appointment));
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function rolesThatCanManageAppointments(): array
    {
        return [
            'administrator' => [Role::ADMINISTRATOR, true],
            'receptionist' => [Role::RECEPTIONIST, true],
            'dentist' => [Role::DENTIST, false],
            'legacy sales role' => [Role::SALES, false],
        ];
    }

    public function test_dentist_can_view_and_handle_only_appointments_assigned_to_their_active_profile(): void
    {
        $dentistUser = $this->userWithRole(Role::DENTIST);
        $otherDentistUser = $this->userWithRole(Role::DENTIST);
        $dentist = Dentist::factory()->for($dentistUser, 'user')->create();
        $otherDentist = Dentist::factory()->for($otherDentistUser, 'user')->create();
        $patient = Patient::factory()->create();
        $appointment = Appointment::factory()->for($patient)->for($dentist)->create();
        $otherAppointment = Appointment::factory()->for($patient)->for($otherDentist)->create();
        $policy = new AppointmentPolicy;

        $this->assertTrue($policy->view($dentistUser, $appointment));
        $this->assertFalse($policy->view($dentistUser, $otherAppointment));
        $this->assertTrue($policy->start($dentistUser, $appointment));
        $this->assertFalse($policy->start($dentistUser, $otherAppointment));
        $this->assertFalse($policy->complete($dentistUser, $appointment));

        $appointment->update(['status' => AppointmentStatus::InTreatment]);

        $this->assertTrue($policy->complete($dentistUser, $appointment));

        $dentist->update(['is_active' => false]);

        $this->assertFalse($policy->view($dentistUser, $appointment));
        $this->assertFalse($policy->start($dentistUser, $appointment));
    }

    public function test_receptionist_cannot_start_or_complete_treatment(): void
    {
        $receptionist = $this->userWithRole(Role::RECEPTIONIST);
        $appointment = Appointment::factory()->create([
            'status' => AppointmentStatus::InTreatment,
        ]);
        $policy = new AppointmentPolicy;

        $this->assertFalse($policy->start($receptionist, $appointment));
        $this->assertFalse($policy->complete($receptionist, $appointment));
    }

    public function test_terminal_appointment_states_cannot_be_reprogrammed_or_transitioned(): void
    {
        $administrator = $this->userWithRole(Role::ADMINISTRATOR);
        $policy = new AppointmentPolicy;

        foreach ([AppointmentStatus::Completed, AppointmentStatus::Cancelled, AppointmentStatus::NoShow] as $status) {
            $appointment = Appointment::factory()->create(['status' => $status]);

            $this->assertFalse($policy->reschedule($administrator, $appointment));
            $this->assertFalse($policy->confirm($administrator, $appointment));
            $this->assertFalse($policy->cancel($administrator, $appointment));
            $this->assertFalse($policy->start($administrator, $appointment));
            $this->assertFalse($policy->complete($administrator, $appointment));
        }
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::query()->firstOrCreate(['name' => $roleName]);

        return User::factory()->for($role)->create(['is_active' => true]);
    }
}
