<?php

namespace Tests\Feature;

use App\Enums\AppointmentReminderStatus;
use App\Enums\AppointmentStatus;
use App\Enums\DentalInvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\TreatmentPlanStatus;
use App\Jobs\SendAppointmentReminder;
use App\Models\Appointment;
use App\Models\AppointmentEvent;
use App\Models\AppointmentReminder;
use App\Models\DentalInvoice;
use App\Models\Patient;
use App\Models\PatientTreatment;
use App\Models\Treatment;
use App\Models\TreatmentPlan;
use App\Models\User;
use App\Notifications\AppointmentReminderNotification;
use App\Services\AppointmentReminderScheduler;
use Carbon\CarbonImmutable;
use Database\Seeders\DentalDemoSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class DentalDemoFlowTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_demo_users_can_follow_the_patient_workflow_only_with_their_permissions(): void
    {
        $this->seedDemo();
        $administrator = $this->demoUser('admin.demo@dental.test');

        $this->post(route('login.store'), [
            'email' => $administrator->email,
            'password' => config('seeders.dental_demo.password'),
        ])->assertRedirectToRoute('dashboard');

        $this->post(route('pacientes.store'), [
            'first_name' => 'Paciente',
            'last_name' => 'Integración DEMO',
            'email' => 'flujo.demo@dental.test',
        ])->assertRedirect();

        $patient = Patient::query()->where('email', 'flujo.demo@dental.test')->firstOrFail();
        $this->put(route('pacientes.update', $patient), [
            'first_name' => 'Paciente',
            'last_name' => 'Actualizado DEMO',
            'email' => $patient->email,
            'is_active' => true,
        ])->assertRedirectToRoute('pacientes.show', $patient);
        $this->get(route('pacientes.show', $patient))->assertOk()->assertSeeText('Actualizado DEMO');
        $this->get(route('pacientes.historial-clinico', $patient))->assertOk();
        $this->post(route('logout'))->assertRedirectToRoute('login');

        $receptionist = $this->demoUser('recepcion.demo@dental.test');
        $this->post(route('login.store'), [
            'email' => $receptionist->email,
            'password' => config('seeders.dental_demo.password'),
        ])->assertRedirectToRoute('dashboard');
        $this->get(route('pacientes.show', $patient))->assertOk();
        $this->get(route('pacientes.historial-clinico', $patient))->assertForbidden();
        $this->get(route('usuarios.index'))->assertForbidden();
        $this->post(route('logout'))->assertRedirectToRoute('login');

        $ana = $this->demoUser('ana.morales@dental.test');
        $this->post(route('login.store'), [
            'email' => $ana->email,
            'password' => config('seeders.dental_demo.password'),
        ])->assertRedirectToRoute('dashboard');
        $maria = Patient::query()->where('email', 'maria.gonzalez.demo@dental.test')->firstOrFail();
        $this->get(route('pacientes.historial-clinico', $maria))->assertOk()->assertSeeText('DEMO F18');
        $this->get(route('pacientes.show', $patient))->assertForbidden();
        $this->get(route('facturacion.reportes.index'))->assertForbidden();
    }

    public function test_demo_appointments_detect_conflicts_and_keep_clinical_history_consistent(): void
    {
        $this->seedDemo();
        $receptionist = $this->demoUser('recepcion.demo@dental.test');
        $joseAppointment = $this->demoAppointment('B');
        $andreaAppointment = $this->demoAppointment('E');
        $timezone = config('appointments.timezone');

        $this->actingAs($receptionist)
            ->put(route('citas.update', $andreaAppointment), [
                'starts_at' => $joseAppointment->starts_at->setTimezone($timezone)->format('Y-m-d\TH:i'),
                'ends_at' => $joseAppointment->ends_at->setTimezone($timezone)->format('Y-m-d\TH:i'),
            ])
            ->assertSessionHasErrors('starts_at');

        $newStart = $joseAppointment->starts_at->setTimezone($timezone)->addHours(3);
        $this->actingAs($receptionist)
            ->put(route('citas.update', $andreaAppointment), [
                'starts_at' => $newStart->format('Y-m-d\TH:i'),
                'ends_at' => $newStart->addHour()->format('Y-m-d\TH:i'),
            ])
            ->assertRedirectToRoute('citas.show', $andreaAppointment);
        $this->assertDatabaseHas('appointment_events', [
            'appointment_id' => $andreaAppointment->id,
            'event_type' => AppointmentEvent::RESCHEDULED,
        ]);

        $this->actingAs($receptionist)
            ->post(route('citas.cancel', $joseAppointment), ['reason' => 'DEMO F18: cancelación de prueba.'])
            ->assertRedirect();
        $this->assertSame(AppointmentStatus::Cancelled, $joseAppointment->fresh()->status);
        $this->assertSame(0, $joseAppointment->treatmentProcedures()->count());

        $carlos = $this->demoUser('carlos.mendez@dental.test');
        $ana = $this->demoUser('ana.morales@dental.test');
        $this->actingAs($carlos)->get(route('citas.show', $andreaAppointment))->assertOk();
        $this->actingAs($carlos)->get(route('pacientes.planes.index', $andreaAppointment->patient))->assertOk();
        $this->actingAs($ana)->get(route('citas.show', $andreaAppointment))->assertForbidden();
    }

    public function test_demo_billing_can_collect_an_outstanding_invoice_in_two_payments(): void
    {
        $this->seedDemo();
        $receptionist = $this->demoUser('recepcion.demo@dental.test');
        $invoice = DentalInvoice::query()->where('status', DentalInvoiceStatus::Issued->value)->firstOrFail();
        $this->assertSame('150.00', $invoice->total);
        $firstKey = (string) Str::uuid();

        $this->actingAs($receptionist)
            ->post(route('facturacion.pagos.store', $invoice), $this->paymentData('50.00', $firstKey))
            ->assertRedirect();
        $invoice->refresh();
        $this->assertSame(DentalInvoiceStatus::PartiallyPaid, $invoice->status);
        $this->assertSame('100.00', $invoice->balance_due);

        $this->actingAs($receptionist)
            ->post(route('facturacion.pagos.store', $invoice), $this->paymentData('50.00', $firstKey))
            ->assertRedirect();
        $this->assertSame(1, $invoice->payments()->count());

        $this->actingAs($receptionist)
            ->post(route('facturacion.pagos.store', $invoice), $this->paymentData('100.00', (string) Str::uuid()))
            ->assertRedirect();
        $invoice->refresh();
        $this->assertSame(DentalInvoiceStatus::Paid, $invoice->status);
        $this->assertSame('0.00', $invoice->balance_due);
        $this->actingAs($receptionist)
            ->get(route('facturacion.facturas.document', $invoice))
            ->assertOk()
            ->assertSeeText('DEMO-DFA-');
    }

    public function test_demo_dentist_can_complete_a_new_plan_with_an_audited_procedure(): void
    {
        $this->seedDemo();
        $ana = $this->demoUser('ana.morales@dental.test');
        $receptionist = $this->demoUser('recepcion.demo@dental.test');
        $appointment = $this->demoAppointment('A');
        $patient = $appointment->patient;
        $treatment = Treatment::query()->where('name', 'Extracción dental')->firstOrFail();

        $this->actingAs($ana)->get(route('tratamientos.index'))->assertOk();
        $this->actingAs($ana)
            ->post(route('pacientes.planes.store', $patient), [
                'title' => 'DEMO F18 - Plan integral de prueba',
                'items' => [['treatment_id' => $treatment->id, 'quantity' => 1]],
            ])
            ->assertRedirect();

        $plan = TreatmentPlan::query()->where('title', 'DEMO F18 - Plan integral de prueba')->firstOrFail();
        $item = $plan->items()->firstOrFail();
        $this->actingAs($receptionist)
            ->post(route('pacientes.tratamientos.store', $patient), [])
            ->assertForbidden();

        $this->actingAs($ana)
            ->post(route('pacientes.tratamientos.store', $patient), [
                'treatment_id' => $treatment->id,
                'appointment_id' => $appointment->id,
                'treatment_plan_item_id' => $item->id,
                'performed_at' => $appointment->starts_at->setTimezone(config('appointments.timezone'))
                    ->addMinutes(35)->format('Y-m-d\TH:i'),
                'procedure_description' => 'DEMO F18: registro simulado.',
                'clinical_notes' => 'DEMO F18: sin diagnóstico real.',
            ])
            ->assertRedirectToRoute('pacientes.historial-clinico', $patient);

        $procedure = PatientTreatment::query()->where('treatment_plan_item_id', $item->id)->firstOrFail();
        $this->assertSame($ana->id, $procedure->registered_by);
        $this->assertSame(TreatmentPlanStatus::Completed, $plan->fresh()->status);
        $this->assertDatabaseHas('patient_treatment_events', [
            'patient_treatment_id' => $procedure->id,
            'user_id' => $ana->id,
            'event_type' => 'created',
        ]);
        $this->actingAs($ana)
            ->get(route('pacientes.historial-clinico', $patient))
            ->assertOk()
            ->assertSeeText('DEMO F18: registro simulado.');
    }

    public function test_demo_reminder_simulation_records_one_attempt_without_sending_real_messages(): void
    {
        $this->seedDemo();
        config(['appointment_reminders.email.enabled' => true]);
        Queue::fake([SendAppointmentReminder::class]);
        Notification::fake();
        Mail::fake();
        $receptionist = $this->demoUser('recepcion.demo@dental.test');
        $appointment = $this->demoAppointment('B');
        $patient = $appointment->patient;
        $scheduler = $this->app->make(AppointmentReminderScheduler::class);

        $this->assertSame(0, $scheduler->dispatchDueReminders());
        $this->actingAs($receptionist)
            ->put(route('pacientes.update', $patient), [
                'first_name' => $patient->first_name,
                'last_name' => $patient->last_name,
                'email' => $patient->email,
                'is_active' => true,
                'email_reminders_enabled' => true,
            ])
            ->assertRedirectToRoute('pacientes.show', $patient);

        $newStart = CarbonImmutable::now(config('appointments.timezone'))->addHours(20);
        $this->actingAs($receptionist)
            ->put(route('citas.update', $appointment), [
                'starts_at' => $newStart->format('Y-m-d\TH:i'),
                'ends_at' => $newStart->addHour()->format('Y-m-d\TH:i'),
            ])
            ->assertRedirectToRoute('citas.show', $appointment);

        $this->assertSame(1, $scheduler->dispatchDueReminders());
        Queue::assertPushed(SendAppointmentReminder::class, 1);
        $reminder = AppointmentReminder::query()->firstOrFail();
        (new SendAppointmentReminder($reminder->id))->handle();

        $this->assertDatabaseHas('appointment_reminder_attempts', [
            'appointment_reminder_id' => $reminder->id,
            'attempt_number' => 1,
            'result' => 'sent',
        ]);
        $this->assertSame(AppointmentReminderStatus::Sent, $reminder->fresh()->status);
        $this->assertSame(0, $scheduler->dispatchDueReminders());
        Notification::assertSentTo($patient, AppointmentReminderNotification::class);
        Mail::assertNothingSent();
    }

    private function seedDemo(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-13 18:00:00', 'UTC'));
        config([
            'seeders.dental_demo.enabled' => true,
            'seeders.dental_demo.database' => ':memory:',
            'seeders.dental_demo.password' => Str::random(20),
        ]);
        $this->seed(DentalDemoSeeder::class);
    }

    private function demoUser(string $email): User
    {
        return User::query()->where('email', $email)->firstOrFail();
    }

    private function demoAppointment(string $scenario): Appointment
    {
        return Appointment::query()->where('administrative_notes', 'DEMO F18 - Escenario '.$scenario)->firstOrFail();
    }

    /** @return array{amount: string, method: string, payment_date: string, idempotency_key: string} */
    private function paymentData(string $amount, string $key): array
    {
        return [
            'amount' => $amount,
            'method' => PaymentMethod::Cash->value,
            'payment_date' => today()->toDateString(),
            'idempotency_key' => $key,
        ];
    }
}
