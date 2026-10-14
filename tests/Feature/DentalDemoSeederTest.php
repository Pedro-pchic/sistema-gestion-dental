<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\DentalInvoiceStatus;
use App\Enums\DentalPaymentStatus;
use App\Enums\TreatmentPlanStatus;
use App\Models\Appointment;
use App\Models\DentalInvoice;
use App\Models\DentalPayment;
use App\Models\Dentist;
use App\Models\Patient;
use App\Models\PatientTreatment;
use App\Models\Role;
use App\Models\Treatment;
use App\Models\TreatmentPlan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\DentalDemoSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class DentalDemoSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_seeder_requires_explicit_authorization_before_creating_records(): void
    {
        $this->expectException(RuntimeException::class);
        $this->seed(DentalDemoSeeder::class);
    }

    public function test_seeder_rejects_production_even_when_database_is_allowlisted(): void
    {
        $this->authorizeDemoDatabase();
        $this->app->detectEnvironment(fn (): string => 'production');

        $this->expectException(RuntimeException::class);
        (new DentalDemoSeeder)->run();
    }

    public function test_seeder_rejects_a_database_outside_the_explicit_allowlist(): void
    {
        $this->authorizeDemoDatabase();
        config(['seeders.dental_demo.database' => 'neondb']);

        $this->expectException(RuntimeException::class);
        $this->seed(DentalDemoSeeder::class);
    }

    public function test_seeder_creates_the_five_demo_scenarios_without_external_messages(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-13 12:00:00', 'UTC'));
        $this->authorizeDemoDatabase();
        Queue::fake();
        Mail::fake();
        Notification::fake();

        $this->seed(DentalDemoSeeder::class);

        $this->assertDatabaseCount('users', 5);
        $this->assertDatabaseCount('dentists', 3);
        $this->assertDatabaseCount('patients', 5);
        $this->assertDatabaseCount('appointments', 5);
        $this->assertDatabaseCount('patient_treatments', 3);
        $this->assertDatabaseCount('treatment_plans', 1);
        $this->assertDatabaseCount('dental_invoices', 3);
        $this->assertDatabaseCount('dental_payments', 2);

        foreach (['admin.demo@dental.test', 'recepcion.demo@dental.test', 'ana.morales@dental.test', 'carlos.mendez@dental.test', 'sofia.lopez@dental.test'] as $email) {
            $user = User::query()->where('email', $email)->firstOrFail();
            $this->assertTrue(Hash::check(config('seeders.dental_demo.password'), $user->password));
        }

        $this->assertSame(Role::ADMINISTRATOR, User::query()->where('email', 'admin.demo@dental.test')->firstOrFail()->role->name);
        $this->assertSame(Role::RECEPTIONIST, User::query()->where('email', 'recepcion.demo@dental.test')->firstOrFail()->role->name);
        $this->assertSame(3, Dentist::query()->whereHas('user.role', fn ($query) => $query->where('name', Role::DENTIST))->count());

        $patients = Patient::query()->get();
        foreach (['María González', 'José Ramírez', 'Lucía Fernández', 'Pedro Castillo', 'Andrea Pérez'] as $name) {
            $this->assertTrue($patients->contains(fn (Patient $patient): bool => $patient->first_name.' '.$patient->last_name === $name));
        }

        foreach (['A' => AppointmentStatus::Completed, 'B' => AppointmentStatus::Scheduled, 'C' => AppointmentStatus::Completed, 'D' => AppointmentStatus::Cancelled, 'E' => AppointmentStatus::Scheduled] as $key => $status) {
            $appointment = Appointment::query()->where('administrative_notes', 'DEMO F18 - Escenario '.$key)->firstOrFail();
            $this->assertSame($status, $appointment->status);
            $this->assertTrue($appointment->patient->dentists()->whereKey($appointment->dentist_id)->exists());
        }

        $this->assertSame(0, PatientTreatment::query()->whereHas('appointment', fn ($query) => $query->where('status', AppointmentStatus::Cancelled->value))->count());
        foreach (PatientTreatment::query()->with('appointment')->get() as $procedure) {
            $this->assertSame($procedure->appointment->patient_id, $procedure->patient_id);
            $this->assertSame($procedure->appointment->dentist_id, $procedure->dentist_id);
            $this->assertTrue($procedure->performed_at->betweenIncluded($procedure->appointment->starts_at, $procedure->appointment->ends_at));
        }
        $this->assertSame(TreatmentPlanStatus::Pending, TreatmentPlan::query()->firstOrFail()->status);
        $this->assertSame(1, Patient::query()->where('email', 'andrea.perez.demo@dental.test')->firstOrFail()->treatmentPlans()->count());
        $this->assertSame(1, DentalInvoice::query()->where('status', DentalInvoiceStatus::Paid->value)->count());
        $this->assertSame(1, DentalInvoice::query()->where('status', DentalInvoiceStatus::PartiallyPaid->value)->count());
        $this->assertSame(1, DentalInvoice::query()->where('status', DentalInvoiceStatus::Issued->value)->count());
        $this->assertSame(3, DentalInvoice::query()->where('number', 'like', 'DEMO-DFA-%')->count());
        $this->assertSame(2, DentalPayment::query()->where('number', 'like', 'DEMO-DREC-%')->count());
        $this->assertSame('0.00', DentalInvoice::query()->where('status', DentalInvoiceStatus::Paid->value)->firstOrFail()->balance_due);
        $this->assertSame('175.00', DentalInvoice::query()->where('status', DentalInvoiceStatus::PartiallyPaid->value)->firstOrFail()->balance_due);
        $this->assertSame('150.00', DentalInvoice::query()->where('status', DentalInvoiceStatus::Issued->value)->firstOrFail()->balance_due);
        $this->assertDatabaseCount('appointment_reminders', 0);
        Queue::assertNothingPushed();
        Mail::assertNothingSent();
        Notification::assertNothingSent();
    }

    public function test_repeated_seeding_preserves_demo_and_unrelated_records(): void
    {
        $this->authorizeDemoDatabase();
        $unrelatedUser = User::factory()->create(['email' => 'unrelated@example.test']);
        $unrelatedPatient = Patient::factory()->create(['email' => 'unrelated-patient@example.test']);
        $existingTreatment = Treatment::factory()->create(['name' => 'Limpieza dental', 'price' => '99.00']);

        $this->seed(DentalDemoSeeder::class);
        $admin = User::query()->where('email', 'admin.demo@dental.test')->firstOrFail();
        $passwordHash = $admin->password;
        $counts = [
            User::query()->count(),
            Patient::query()->count(),
            Appointment::query()->count(),
            PatientTreatment::query()->count(),
            DentalInvoice::query()->count(),
        ];

        $this->seed(DentalDemoSeeder::class);

        $this->assertSame($counts, [
            User::query()->count(),
            Patient::query()->count(),
            Appointment::query()->count(),
            PatientTreatment::query()->count(),
            DentalInvoice::query()->count(),
        ]);
        $this->assertSame($passwordHash, $admin->fresh()->password);
        $this->assertDatabaseHas('users', ['id' => $unrelatedUser->id, 'email' => 'unrelated@example.test']);
        $this->assertDatabaseHas('patients', ['id' => $unrelatedPatient->id, 'email' => 'unrelated-patient@example.test']);
        $this->assertDatabaseHas('treatments', ['id' => $existingTreatment->id, 'name' => 'Limpieza dental', 'price' => '99.00']);
    }

    public function test_seeder_aborts_without_changing_a_colliding_existing_user(): void
    {
        $this->authorizeDemoDatabase();
        $existing = User::factory()->create(['email' => 'admin.demo@dental.test', 'name' => 'Usuario existente']);

        try {
            $this->seed(DentalDemoSeeder::class);
            $this->fail('La colisión DEMO debía detener el seeder.');
        } catch (RuntimeException) {
            $this->assertDatabaseHas('users', ['id' => $existing->id, 'name' => 'Usuario existente']);
            $this->assertDatabaseCount('users', 1);
            $this->assertDatabaseCount('patients', 0);
        }
    }

    public function test_neondb_mode_rejects_even_a_matching_demo_user_before_writing(): void
    {
        $this->authorizeDemoDatabase();
        config(['seeders.dental_demo.allow_neondb' => true]);
        $role = Role::query()->create(['name' => Role::ADMINISTRATOR]);
        $existing = User::factory()->create([
            'name' => 'Administrador Demo',
            'email' => 'admin.demo@dental.test',
            'role_id' => $role->id,
            'is_active' => true,
            'can_view_clinical_records' => true,
        ]);

        try {
            $this->seed(DentalDemoSeeder::class);
            $this->fail('La cuenta DEMO existente debía impedir la carga en neondb.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString(strtolower($existing->email), $exception->getMessage());
            $this->assertDatabaseCount('users', 1);
            $this->assertDatabaseCount('patients', 0);
            $this->assertDatabaseCount('appointments', 0);
        }
    }

    public function test_neondb_mode_rejects_a_patient_with_the_same_name_before_writing(): void
    {
        $this->authorizeDemoDatabase();
        config(['seeders.dental_demo.allow_neondb' => true]);
        $existing = Patient::factory()->create([
            'first_name' => 'María',
            'last_name' => 'González',
            'email' => 'otra-persona@example.test',
        ]);

        try {
            $this->seed(DentalDemoSeeder::class);
            $this->fail('El paciente coincidente debía impedir la carga en neondb.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('maria.gonzalez.demo@dental.test', $exception->getMessage());
            $this->assertDatabaseHas('patients', ['id' => $existing->id, 'email' => 'otra-persona@example.test']);
            $this->assertDatabaseCount('users', 0);
            $this->assertDatabaseCount('patients', 1);
        }
    }

    public function test_neondb_mode_rejects_a_patient_with_the_same_email_before_writing(): void
    {
        $this->authorizeDemoDatabase();
        config(['seeders.dental_demo.allow_neondb' => true]);
        $existing = Patient::factory()->create(['email' => 'MARIA.GONZALEZ.DEMO@DENTAL.TEST']);

        try {
            $this->seed(DentalDemoSeeder::class);
            $this->fail('El correo del paciente DEMO debía impedir la carga en neondb.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString(strtolower($existing->email), $exception->getMessage());
            $this->assertDatabaseCount('users', 0);
            $this->assertDatabaseCount('patients', 1);
        }
    }

    public function test_repeated_seeding_rejects_a_changed_demo_payment(): void
    {
        $this->authorizeDemoDatabase();
        $this->seed(DentalDemoSeeder::class);
        $payment = DentalPayment::query()->firstOrFail();
        $payment->update(['status' => DentalPaymentStatus::Cancelled]);

        $this->expectException(RuntimeException::class);
        $this->seed(DentalDemoSeeder::class);
    }

    private function authorizeDemoDatabase(): void
    {
        config([
            'seeders.dental_demo.enabled' => true,
            'seeders.dental_demo.database' => ':memory:',
            'seeders.dental_demo.password' => Str::random(20),
            'seeders.dental_demo.allow_neondb' => false,
        ]);
    }
}
