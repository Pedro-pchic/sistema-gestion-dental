<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\DentalInvoiceStatus;
use App\Enums\DentalPaymentStatus;
use App\Models\Appointment;
use App\Models\DentalInvoice;
use App\Models\DentalPayment;
use App\Models\Dentist;
use App\Models\Patient;
use App\Models\PatientTreatment;
use App\Models\Role;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DentalDashboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_administrator_sees_filtered_appointment_treatment_and_financial_metrics(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-12 10:00:00', 'America/Guatemala'));
        $administrator = $this->userWithRole(Role::ADMINISTRATOR);
        $patient = Patient::factory()->create(['is_active' => true]);

        $this->appointment($patient, AppointmentStatus::Scheduled);
        $this->appointment($patient, AppointmentStatus::Confirmed);
        $this->appointment($patient, AppointmentStatus::Cancelled);
        $this->appointment($patient, AppointmentStatus::Completed);
        $yesterday = CarbonImmutable::now('UTC')->subDay();
        $this->appointment($patient, AppointmentStatus::Confirmed, null, [
            'starts_at' => $yesterday,
            'ends_at' => $yesterday->addHour(),
        ]);
        PatientTreatment::factory()->create([
            'patient_id' => $patient->getKey(),
            'performed_at' => CarbonImmutable::now('UTC'),
            'clinical_notes' => 'Confidencial y no visible en el panel.',
        ]);
        PatientTreatment::factory()->create([
            'patient_id' => $patient->getKey(),
            'performed_at' => CarbonImmutable::now('UTC')->subDay(),
        ]);
        $invoice = DentalInvoice::factory()->issued()->for($patient)->for($administrator, 'creator')->create([
            'issue_date' => '2026-10-12',
            'balance_due' => '75.00',
        ]);
        DentalPayment::factory()->for($invoice, 'invoice')->for($administrator, 'recorder')->create([
            'amount' => '25.00',
            'payment_date' => '2026-10-12',
            'status' => DentalPaymentStatus::Registered,
        ]);
        $previousInvoice = DentalInvoice::factory()->issued()->for($patient)->for($administrator, 'creator')->create([
            'issue_date' => '2026-10-11',
            'status' => DentalInvoiceStatus::Paid,
            'balance_due' => '0.00',
        ]);
        DentalPayment::factory()->for($previousInvoice, 'invoice')->for($administrator, 'recorder')->create([
            'amount' => '90.00',
            'payment_date' => '2026-10-11',
            'status' => DentalPaymentStatus::Registered,
        ]);

        $response = $this->actingAs($administrator)
            ->get(route('dashboard', ['from' => '2026-10-12', 'to' => '2026-10-12']))
            ->assertOk()
            ->assertViewIs('dashboard.dental');
        $metrics = $response->viewData('dashboardMetrics');

        $this->assertSame(1, $metrics['active_patients']);
        $this->assertSame(1, $metrics['appointments_today']);
        $this->assertSame(1, $metrics['confirmed_appointments']);
        $this->assertSame(1, $metrics['cancelled_appointments']);
        $this->assertSame(1, $metrics['completed_appointments']);
        $this->assertSame(1, $metrics['treatments_completed']);
        $this->assertSame(1, $metrics['issued_invoice_count']);
        $this->assertSame(1, $metrics['pending_invoice_count']);
        $this->assertSame(75.0, (float) $metrics['pending_balance']);
        $this->assertSame(25.0, (float) $metrics['registered_income']);
        $response->assertSeeText('Ingresos registrados')
            ->assertSeeText('Saldos pendientes')
            ->assertDontSeeText('Confidencial y no visible en el panel.');
    }

    public function test_receptionist_sees_pending_collections_without_revenue_totals(): void
    {
        $receptionist = $this->userWithRole(Role::RECEPTIONIST);
        $patient = Patient::factory()->create(['first_name' => 'Paciente', 'last_name' => 'Cobro']);
        $invoice = DentalInvoice::factory()->issued()->for($patient)->create(['balance_due' => '50.00']);
        DentalPayment::factory()->for($invoice, 'invoice')->create(['amount' => '50.00']);

        $response = $this->actingAs($receptionist)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewIs('dashboard.dental')
            ->assertSeeText('Cobros pendientes')
            ->assertSeeText('Paciente Cobro')
            ->assertDontSeeText('Ingresos registrados');
        $metrics = $response->viewData('dashboardMetrics');

        $this->assertSame(1, $metrics['pending_invoice_count']);
        $this->assertSame(50.0, (float) $metrics['pending_balance']);
        $this->assertArrayNotHasKey('registered_income', $metrics);
        $this->assertArrayNotHasKey('issued_invoice_count', $metrics);
    }

    public function test_dentist_sees_only_assigned_patients_and_own_treatments_without_financial_data(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-12 10:00:00', 'America/Guatemala'));
        $dentistUser = $this->userWithRole(Role::DENTIST);
        $dentist = Dentist::factory()->for($dentistUser, 'user')->create();
        $otherDentist = Dentist::factory()->create();
        $assignedPatient = Patient::factory()->create(['first_name' => 'Paciente', 'last_name' => 'Asignado']);
        $otherPatient = Patient::factory()->create(['first_name' => 'Paciente', 'last_name' => 'Ajeno']);
        $dentist->patients()->attach($assignedPatient);
        $otherDentist->patients()->attach($otherPatient);
        $this->appointment($assignedPatient, AppointmentStatus::Confirmed, $dentist);
        $this->appointment($otherPatient, AppointmentStatus::Confirmed, $otherDentist);
        PatientTreatment::factory()->for($dentist, 'dentist')->for($assignedPatient)->create();

        $response = $this->actingAs($dentistUser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewIs('dashboard.dental')
            ->assertSeeText('Paciente Asignado')
            ->assertDontSeeText('Paciente Ajeno')
            ->assertDontSeeText('Ingresos registrados')
            ->assertDontSeeText('Cobros pendientes');
        $metrics = $response->viewData('dashboardMetrics');

        $this->assertSame(1, $metrics['active_patients']);
        $this->assertSame(1, $metrics['confirmed_appointments']);
        $this->assertSame(1, $metrics['treatments_completed']);
        $this->assertArrayNotHasKey('registered_income', $metrics);
        $this->assertArrayNotHasKey('pending_balance', $metrics);
    }

    public function test_unrecognized_role_cannot_view_dental_dashboard(): void
    {
        $user = $this->userWithRole(Role::SALES);

        $this->actingAs($user)->get(route('dashboard'))->assertForbidden();
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::query()->firstOrCreate(['name' => $roleName]);

        return User::factory()->for($role)->create(['is_active' => true]);
    }

    private function appointment(
        Patient $patient,
        AppointmentStatus $status,
        ?Dentist $dentist = null,
        array $attributes = [],
    ): Appointment {
        $startsAt = CarbonImmutable::now('UTC')->addHours(2);
        $appointment = Appointment::factory()->for($patient);

        if ($dentist !== null) {
            $appointment = $appointment->for($dentist);
        }

        return $appointment->create(array_merge([
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->addHour(),
            'status' => $status,
        ], $attributes));
    }
}
