<?php

namespace Tests\Feature;

use App\Enums\AppointmentReminderStatus;
use App\Enums\AppointmentStatus;
use App\Enums\DentalInvoiceStatus;
use App\Enums\DentalPaymentStatus;
use App\Enums\TreatmentPlanStatus;
use App\Models\Appointment;
use App\Models\AppointmentEvent;
use App\Models\AppointmentReminder;
use App\Models\DentalInvoice;
use App\Models\DentalPayment;
use App\Models\Dentist;
use App\Models\Patient;
use App\Models\PatientTreatment;
use App\Models\Role;
use App\Models\TreatmentPlan;
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

        $scheduledAppointment = $this->appointment($patient, AppointmentStatus::Scheduled);
        $this->appointment($patient, AppointmentStatus::Confirmed);
        $this->appointment($patient, AppointmentStatus::Cancelled);
        $this->appointment($patient, AppointmentStatus::Completed);
        AppointmentReminder::query()->create([
            'appointment_id' => $scheduledAppointment->getKey(),
            'patient_id' => $patient->getKey(),
            'channel' => 'email',
            'appointment_starts_at' => $scheduledAppointment->starts_at,
            'scheduled_at' => $scheduledAppointment->starts_at->subHours(24),
            'status' => AppointmentReminderStatus::Pending,
        ]);
        AppointmentEvent::query()->create([
            'appointment_id' => $scheduledAppointment->getKey(),
            'user_id' => $administrator->getKey(),
            'event_type' => AppointmentEvent::CREATED,
        ]);
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
        DentalInvoice::factory()->issued()->for($patient)->for($administrator, 'creator')->create([
            'issue_date' => '2026-10-11',
            'balance_due' => '40.00',
        ]);

        $response = $this->actingAs($administrator)
            ->get(route('dashboard', ['from' => '2026-10-12', 'to' => '2026-10-12']))
            ->assertOk()
            ->assertViewIs('dashboard.dental');
        $metrics = $response->viewData('dashboardMetrics');

        $this->assertSame('2026-10-12', $response->viewData('from'));
        $this->assertSame('2026-10-12', $response->viewData('to'));
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
        $this->assertSame(1, $metrics['notification_status']['pending']);
        $this->assertSame(0, $metrics['notification_status']['failed_last_7_days']);
        $this->assertCount(1, $metrics['recent_activity']);
        $response->assertSeeText('Ingresos registrados')
            ->assertSeeText('Saldos pendientes')
            ->assertSeeText('Ver recordatorios')
            ->assertSeeText('Cita programada')
            ->assertDontSeeText('Confidencial y no visible en el panel.');
    }

    public function test_receptionist_sees_pending_collections_without_revenue_totals(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-12 10:00:00', 'America/Guatemala'));
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
            ->assertSeeText('Ver recordatorios')
            ->assertDontSeeText('Ingresos registrados');
        $metrics = $response->viewData('dashboardMetrics');

        $this->assertSame(1, $metrics['pending_invoice_count']);
        $this->assertSame(50.0, (float) $metrics['pending_balance']);
        $this->assertArrayNotHasKey('registered_income', $metrics);
        $this->assertArrayNotHasKey('issued_invoice_count', $metrics);
        $this->assertArrayNotHasKey('issued_total', $metrics);
        $this->assertArrayNotHasKey('revenue_by_day', $metrics);
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
        $assignedAppointment = $this->appointment($assignedPatient, AppointmentStatus::Confirmed, $dentist);
        $otherAppointment = $this->appointment($otherPatient, AppointmentStatus::Confirmed, $otherDentist);
        foreach ([$assignedAppointment, $otherAppointment] as $appointment) {
            AppointmentEvent::query()->create([
                'appointment_id' => $appointment->getKey(),
                'user_id' => $dentistUser->getKey(),
                'event_type' => AppointmentEvent::CONFIRMED,
            ]);
        }
        PatientTreatment::factory()->for($dentist, 'dentist')->for($assignedPatient)->create();

        $response = $this->actingAs($dentistUser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewIs('dashboard.dental')
            ->assertSeeText('Paciente Asignado')
            ->assertDontSeeText('Paciente Ajeno')
            ->assertDontSeeText('Ingresos registrados')
            ->assertDontSeeText('Ver recordatorios')
            ->assertDontSeeText('Cobros pendientes');
        $metrics = $response->viewData('dashboardMetrics');

        $this->assertSame(1, $metrics['active_patients']);
        $this->assertSame(1, $metrics['confirmed_appointments']);
        $this->assertSame(1, $metrics['treatments_completed']);
        $this->assertCount(1, $metrics['recent_activity']);
        $this->assertSame($assignedAppointment->getKey(), $metrics['recent_activity']->first()->appointment_id);
        $this->assertArrayNotHasKey('registered_income', $metrics);
        $this->assertArrayNotHasKey('pending_balance', $metrics);
        $this->assertArrayNotHasKey('received_total', $metrics);
        $this->assertArrayNotHasKey('revenue_by_day', $metrics);
        $this->assertArrayNotHasKey('pending', $metrics['notification_status']);
    }

    public function test_unrecognized_role_cannot_view_dental_dashboard(): void
    {
        $user = $this->userWithRole(Role::SALES);

        $this->actingAs($user)->get(route('dashboard'))->assertForbidden();
    }

    public function test_administrator_can_filter_local_activity_by_dentist_without_counting_future_procedures(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-12 10:00:00', 'America/Guatemala'));
        $administrator = $this->userWithRole(Role::ADMINISTRATOR);
        $dentist = Dentist::factory()->create();
        $otherDentist = Dentist::factory()->create();
        $patient = Patient::factory()->create();
        $otherPatient = Patient::factory()->create();
        $dentist->patients()->attach($patient);
        $otherDentist->patients()->attach($otherPatient);
        $startsAt = CarbonImmutable::parse('2026-10-06 06:30:00', 'UTC');
        $this->appointment($patient, AppointmentStatus::Scheduled, $dentist, ['starts_at' => $startsAt, 'ends_at' => $startsAt->addHour()]);
        $this->appointment($otherPatient, AppointmentStatus::Confirmed, $otherDentist, ['starts_at' => $startsAt, 'ends_at' => $startsAt->addHour()]);
        $outsidePeriod = $startsAt->subHour();
        $this->appointment($patient, AppointmentStatus::Completed, $dentist, ['starts_at' => $outsidePeriod, 'ends_at' => $outsidePeriod->addHour()]);
        PatientTreatment::factory()->for($patient)->for($dentist)->create(['performed_at' => now('UTC')]);
        PatientTreatment::factory()->for($patient)->for($dentist)->create(['performed_at' => now('UTC')->addDay()]);
        PatientTreatment::factory()->for($otherPatient)->for($otherDentist)->create(['performed_at' => now('UTC')]);
        TreatmentPlan::factory()->for($patient)->for($dentist)->create(['status' => TreatmentPlanStatus::InProgress]);
        TreatmentPlan::factory()->for($otherPatient)->for($otherDentist)->create(['status' => TreatmentPlanStatus::InProgress]);

        $response = $this->actingAs($administrator)->get(route('dashboard', [
            'period' => 'last_7_days',
            'dentist_id' => $dentist->id,
        ]))->assertOk();
        $metrics = $response->viewData('dashboardMetrics');

        $this->assertSame('2026-10-06', $response->viewData('from'));
        $this->assertSame('2026-10-12', $response->viewData('to'));
        $this->assertSame(1, $metrics['active_patients']);
        $this->assertSame(1, $metrics['registered_patients']);
        $this->assertSame(1, $metrics['scheduled_appointments']);
        $this->assertSame(0, $metrics['confirmed_appointments']);
        $this->assertSame('2026-10-06', $metrics['appointment_trend']->first()->day);
        $this->assertSame(1, $metrics['treatments_completed']);
        $this->assertSame(1, $metrics['top_treatments']->first()->aggregate);
        $this->assertSame(1, $metrics['active_plans']);
        $this->assertCount(1, $metrics['dentist_activity']);
        $this->assertSame($dentist->id, $metrics['dentist_activity']->first()->id);
        $response->assertDontSeeText($otherPatient->first_name.' '.$otherPatient->last_name);
    }

    public function test_financial_dashboard_matches_report_and_excludes_cancelled_records(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-12 23:00:00', 'America/Guatemala'));
        $administrator = $this->userWithRole(Role::ADMINISTRATOR);
        $patient = Patient::factory()->create();
        $invoice = DentalInvoice::factory()->issued()->for($patient)->for($administrator, 'creator')->create([
            'issue_date' => '2026-10-12', 'total' => '100.00', 'balance_due' => '70.00',
        ]);
        DentalInvoice::factory()->issued()->for($patient)->for($administrator, 'creator')->create([
            'issue_date' => '2026-10-12', 'status' => DentalInvoiceStatus::Paid, 'total' => '40.00', 'balance_due' => '0.00',
        ]);
        DentalInvoice::factory()->issued()->for($patient)->for($administrator, 'creator')->create([
            'issue_date' => '2026-10-12', 'status' => DentalInvoiceStatus::Cancelled, 'total' => '500.00', 'cancelled_at' => now('UTC'),
        ]);
        DentalPayment::factory()->for($invoice, 'invoice')->for($administrator, 'recorder')->create([
            'payment_date' => '2026-10-12', 'amount' => '30.00', 'status' => DentalPaymentStatus::Registered,
        ]);
        DentalPayment::factory()->for($invoice, 'invoice')->for($administrator, 'recorder')->create([
            'payment_date' => '2026-10-12', 'amount' => '50.00', 'status' => DentalPaymentStatus::Cancelled,
        ]);

        $filters = ['from' => '2026-10-12', 'to' => '2026-10-12'];
        $metrics = $this->actingAs($administrator)->get(route('dashboard', $filters))->assertOk()->viewData('dashboardMetrics');
        $report = $this->actingAs($administrator)->get(route('facturacion.reportes.index', $filters))->assertOk();

        $this->assertSame(2, $metrics['issued_invoice_count']);
        $this->assertSame('140.00', $metrics['issued_total']);
        $this->assertSame('30.00', $metrics['received_total']);
        $this->assertSame('70.00', $metrics['pending_balance']);
        $this->assertSame(1, $metrics['cancelled_invoice_count']);
        $this->assertSame(3000, $metrics['revenue_by_day']->first()->amount_cents);
        $this->assertSame(14000, $report->viewData('issuedInvoiceCents'));
        $this->assertSame(3000, $report->viewData('receivedCents'));
        $this->assertSame(7000, $report->viewData('pendingCents'));
        $this->assertSame(1, $report->viewData('cancelledInvoices'));
    }

    public function test_dashboard_rejects_unauthorized_dentist_filters_and_large_date_ranges(): void
    {
        $administrator = $this->userWithRole(Role::ADMINISTRATOR);
        $dentistUser = $this->userWithRole(Role::DENTIST);
        $receptionist = $this->userWithRole(Role::RECEPTIONIST);
        $dentist = Dentist::factory()->for($dentistUser, 'user')->create();

        $this->actingAs($dentistUser)->get(route('dashboard', ['dentist_id' => $dentist->id]))->assertForbidden();
        $this->actingAs($receptionist)->get(route('dashboard', ['dentist_id' => $dentist->id]))->assertForbidden();
        $this->actingAs($administrator)->get(route('dashboard', ['dentist_id' => 999999]))->assertSessionHasErrors('dentist_id');
        $this->actingAs($administrator)->get(route('dashboard', ['from' => '2026-09-01', 'to' => '2026-10-03']))->assertSessionHasErrors('to');
        $this->actingAs($administrator)->get(route('dashboard', ['from' => '2026-10-03', 'to' => '2026-09-01']))->assertSessionHasErrors('to');
    }

    public function test_dashboard_omits_deleted_patients_and_handles_empty_period(): void
    {
        $administrator = $this->userWithRole(Role::ADMINISTRATOR);
        Patient::factory()->create(['is_active' => false]);
        $deletedPatient = Patient::factory()->create(['is_active' => true]);
        $deletedPatient->delete();

        $response = $this->actingAs($administrator)->get(route('dashboard', ['from' => '2026-09-01', 'to' => '2026-09-30']))->assertOk();
        $metrics = $response->viewData('dashboardMetrics');

        $this->assertSame(0, $metrics['active_patients']);
        $this->assertSame(0, $metrics['scheduled_appointments']);
        $this->assertSame(0, $metrics['treatments_completed']);
        $this->assertSame('0.00', $metrics['issued_total']);
        $this->assertTrue($metrics['appointment_trend']->isEmpty());
        $response->assertSeeText('No hay citas en este período.');
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
