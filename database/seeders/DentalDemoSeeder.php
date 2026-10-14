<?php

namespace Database\Seeders;

use App\Actions\CreateDentalInvoiceDraft;
use App\Actions\IssueDentalInvoice;
use App\Actions\RegisterDentalPayment;
use App\Enums\AppointmentStatus;
use App\Enums\DentalInvoiceStatus;
use App\Enums\DentalPaymentStatus;
use App\Enums\PaymentMethod;
use App\Enums\TreatmentPlanStatus;
use App\Models\Appointment;
use App\Models\AppointmentEvent;
use App\Models\DentalInvoice;
use App\Models\DentalInvoiceDetail;
use App\Models\DentalPayment;
use App\Models\Dentist;
use App\Models\Patient;
use App\Models\PatientTreatment;
use App\Models\PatientTreatmentEvent;
use App\Models\Role;
use App\Models\Treatment;
use App\Models\TreatmentPlan;
use App\Models\User;
use App\Services\AppointmentScheduler;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DentalDemoSeeder extends Seeder
{
    private const array USERS = [
        'admin' => ['Administrador Demo', 'admin.demo@dental.test', Role::ADMINISTRATOR],
        'reception' => ['Recepción Demo', 'recepcion.demo@dental.test', Role::RECEPTIONIST],
        'ana' => ['Dra. Ana Morales', 'ana.morales@dental.test', Role::DENTIST],
        'carlos' => ['Dr. Carlos Méndez', 'carlos.mendez@dental.test', Role::DENTIST],
        'sofia' => ['Dra. Sofía López', 'sofia.lopez@dental.test', Role::DENTIST],
    ];

    private const array PATIENTS = [
        'maria' => ['María', 'González', 'maria.gonzalez.demo@dental.test', '1990-03-14', 'ana'],
        'jose' => ['José', 'Ramírez', 'jose.ramirez.demo@dental.test', '1984-06-21', 'carlos'],
        'lucia' => ['Lucía', 'Fernández', 'lucia.fernandez.demo@dental.test', '1996-09-08', 'sofia'],
        'pedro' => ['Pedro', 'Castillo', 'pedro.castillo.demo@dental.test', '1978-11-19', 'ana'],
        'andrea' => ['Andrea', 'Pérez', 'andrea.perez.demo@dental.test', '1992-01-27', 'carlos'],
    ];

    private const array TREATMENTS = [
        'cleaning' => ['Limpieza dental', '250.00'],
        'extraction' => ['Extracción dental', '400.00'],
        'resin' => ['Resina dental', '350.00'],
        'evaluation' => ['Evaluación odontológica', '150.00'],
        'root_canal' => ['Tratamiento de conducto', '850.00'],
    ];

    public function run(): void
    {
        $this->assertSafeDatabase();

        if (config('seeders.dental_demo.allow_neondb') === true) {
            $this->assertNoExistingDemoIdentities();
        }

        DB::transaction(function (): void {
            $users = $this->createUsers();
            $dentists = $this->createDentists($users);
            $patients = $this->createPatients($dentists);
            $treatments = $this->createTreatments();
            $appointments = $this->createAppointments($users['reception'], $patients, $dentists, $treatments);

            $cleaning = $this->createProcedure($appointments['A'], $treatments['cleaning'], $users['ana']);
            $evaluation = $this->createProcedure($appointments['A'], $treatments['evaluation'], $users['ana']);
            $resin = $this->createProcedure($appointments['C'], $treatments['resin'], $users['sofia']);
            $this->createPendingPlan($patients['andrea'], $dentists['carlos'], $treatments['root_canal'], $users['carlos']);

            $paidInvoice = $this->createInvoice($patients['maria'], $cleaning, $users['admin']);
            $partialInvoice = $this->createInvoice($patients['lucia'], $resin, $users['admin']);
            $this->createInvoice($patients['maria'], $evaluation, $users['admin']);

            $this->createPayment($paidInvoice, $paidInvoice->total, '00000000-0000-4000-8000-000000000018', $users['reception']);
            $partialAmount = Money::fromCents(intdiv(Money::toCents($partialInvoice->total), 2));
            $this->createPayment($partialInvoice, $partialAmount, '00000000-0000-4000-8000-000000000019', $users['reception']);
        });
    }

    private function assertSafeDatabase(): void
    {
        $connection = DB::connection();
        $database = $connection->getDatabaseName();
        $driver = $connection->getDriverName();
        $allowedDatabase = config('seeders.dental_demo.database');
        $password = config('seeders.dental_demo.password');
        $approvedNeonHost = config('seeders.dental_demo.neondb_host');
        $isDemoDatabase = is_string($database)
            && ($database === ':memory:' ? app()->environment('testing') : str_contains(strtolower($database), 'demo'));
        $isApprovedNeondb = $driver === 'pgsql'
            && $database === 'neondb'
            && config('seeders.dental_demo.allow_neondb') === true
            && is_string($approvedNeonHost)
            && str_ends_with(strtolower($approvedNeonHost), '.neon.tech')
            && $connection->getConfig('host') === $approvedNeonHost
            && config('mail.default') === 'array'
            && config('queue.default') === 'database'
            && config('queue.connections.database.connection') === 'pgsql'
            && config('appointment_reminders.email.enabled') === false
            && config('appointment_reminders.sms.enabled') === false
            && config('appointment_reminders.sms.driver') === 'disabled';

        if ($database === 'neondb' && ! $isApprovedNeondb) {
            throw new RuntimeException('neondb exige autorización explícita, host Neon exacto y canales externos desactivados.');
        }

        if (! app()->environment(['local', 'testing'])
            || config('seeders.dental_demo.enabled') !== true
            || ! is_string($allowedDatabase)
            || $database !== $allowedDatabase
            || ! in_array($driver, ['pgsql', 'sqlite'], true)
            || (! $isDemoDatabase && ! $isApprovedNeondb)
            || ! is_string($password)
            || mb_strlen($password) < 12) {
            throw new RuntimeException('Se requiere un entorno local o de pruebas, un destino DEMO autorizado y DENTAL_DEMO_PASSWORD de al menos 12 caracteres.');
        }

        if ($isApprovedNeondb && $connection->selectOne('SELECT current_database() AS database_name')->database_name !== 'neondb') {
            throw new RuntimeException('La conexión PostgreSQL no apunta a la base neondb autorizada.');
        }
    }

    private function assertNoExistingDemoIdentities(): void
    {
        $existingEmails = User::query()->whereIn(DB::raw('LOWER(email)'), array_column(self::USERS, 1))->pluck('email')->all();

        if ($existingEmails !== []) {
            throw new RuntimeException('Ya existen usuarios con correos DEMO: '.implode(', ', $existingEmails));
        }

        foreach (self::PATIENTS as [$firstName, $lastName, $email]) {
            $patientExists = Patient::withTrashed()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->orWhere(fn (Builder $query): Builder => $query->where('first_name', $firstName)->where('last_name', $lastName))
                ->exists();

            if ($patientExists) {
                throw new RuntimeException('Ya existe un paciente con correo o nombre DEMO: '.$email);
            }
        }
    }

    /** @return array<string, User> */
    private function createUsers(): array
    {
        $users = [];

        foreach (self::USERS as $key => [$name, $email, $roleName]) {
            $role = Role::query()->firstOrCreate(['name' => $roleName]);
            $user = User::query()->where('email', $email)->first();

            if ($user === null) {
                $user = User::query()->create([
                    'name' => $name,
                    'email' => $email,
                    'role_id' => $role->id,
                    'is_active' => true,
                    'can_view_clinical_records' => $key === 'admin',
                    'password' => config('seeders.dental_demo.password'),
                ]);
            } elseif ($user->name !== $name || $user->role_id !== $role->id || ! $user->is_active
                || $user->can_view_clinical_records !== ($key === 'admin')) {
                throw new RuntimeException('Existe un usuario incompatible con el identificador DEMO: '.$email);
            }

            $users[$key] = $user;
        }

        return $users;
    }

    /** @param array<string, User> $users
     * @return array<string, Dentist>
     */
    private function createDentists(array $users): array
    {
        $dentists = [];

        foreach (['ana', 'carlos', 'sofia'] as $key) {
            $dentist = Dentist::query()->firstOrCreate(['user_id' => $users[$key]->id], ['is_active' => true]);

            if (! $dentist->is_active) {
                throw new RuntimeException('El perfil de odontólogo DEMO está inactivo: '.$key);
            }

            $dentists[$key] = $dentist;
        }

        return $dentists;
    }

    /** @param array<string, Dentist> $dentists
     * @return array<string, Patient>
     */
    private function createPatients(array $dentists): array
    {
        $patients = [];

        foreach (self::PATIENTS as $key => [$firstName, $lastName, $email, $birthDate, $dentistKey]) {
            $address = 'DEMO F18 - Dirección ficticia, Ciudad de Guatemala';
            $patient = Patient::withTrashed()->where('email', $email)->first();

            if ($patient === null) {
                $patient = Patient::query()->create([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'date_of_birth' => $birthDate,
                    'phone' => '0000000000',
                    'address' => $address,
                    'is_active' => true,
                    'email_reminders_enabled' => false,
                    'sms_reminders_enabled' => false,
                ]);
            } elseif ($patient->first_name !== $firstName || $patient->last_name !== $lastName
                || $patient->address !== $address || $patient->trashed() || ! $patient->is_active) {
                throw new RuntimeException('Existe un paciente incompatible con el identificador DEMO: '.$email);
            }

            $patient->dentists()->syncWithoutDetaching([$dentists[$dentistKey]->id]);
            $patients[$key] = $patient;
        }

        return $patients;
    }

    /** @return array<string, Treatment> */
    private function createTreatments(): array
    {
        $treatments = [];

        foreach (self::TREATMENTS as $key => [$name, $price]) {
            $treatment = Treatment::query()->firstOrCreate(['name' => $name], [
                'description' => 'DEMO F18 - Tratamiento ficticio para demostración académica.',
                'price' => $price,
                'estimated_duration_minutes' => 60,
                'is_active' => true,
            ]);

            if (! $treatment->is_active || Money::toCents($treatment->price) <= 1) {
                throw new RuntimeException('El tratamiento requerido para DEMO está inactivo o no es facturable: '.$name);
            }

            $treatments[$key] = $treatment;
        }

        return $treatments;
    }

    /** @param array<string, Patient> $patients
     * @param  array<string, Dentist>  $dentists
     * @param  array<string, Treatment>  $treatments
     * @return array<string, Appointment>
     */
    private function createAppointments(User $receptionist, array $patients, array $dentists, array $treatments): array
    {
        $today = CarbonImmutable::now(config('appointments.timezone'));
        $scenarios = [
            'A' => [$patients['maria'], $dentists['ana'], $treatments['cleaning'], AppointmentStatus::Completed, $today->subDays(5)->setTime(9, 0)],
            'B' => [$patients['jose'], $dentists['carlos'], $treatments['extraction'], AppointmentStatus::Scheduled, $today->addDays(7)->setTime(9, 0)],
            'C' => [$patients['lucia'], $dentists['sofia'], $treatments['resin'], AppointmentStatus::Completed, $today->subDays(3)->setTime(10, 0)],
            'D' => [$patients['pedro'], $dentists['ana'], $treatments['evaluation'], AppointmentStatus::Cancelled, $today->subDays(2)->setTime(11, 0)],
            'E' => [$patients['andrea'], $dentists['carlos'], $treatments['root_canal'], AppointmentStatus::Scheduled, $today->addDays(7)->setTime(11, 0)],
        ];
        $appointments = [];

        foreach ($scenarios as $key => [$patient, $dentist, $treatment, $status, $startsAt]) {
            $marker = 'DEMO F18 - Escenario '.$key;
            $appointment = Appointment::query()->where('administrative_notes', $marker)->first();

            if ($appointment !== null) {
                if ($appointment->patient_id !== $patient->id || $appointment->dentist_id !== $dentist->id
                    || $appointment->status !== $status) {
                    throw new RuntimeException('Existe una cita incompatible con el identificador '.$marker);
                }
            } elseif ($status === AppointmentStatus::Scheduled) {
                $appointment = (new AppointmentScheduler)->create([
                    'patient_id' => $patient->id,
                    'dentist_id' => $dentist->id,
                    'starts_at' => $startsAt->format('Y-m-d\TH:i'),
                    'ends_at' => $startsAt->addHour()->format('Y-m-d\TH:i'),
                    'planned_treatment' => $treatment->name,
                    'administrative_notes' => $marker,
                ], $receptionist);
            } else {
                $appointment = Appointment::query()->create([
                    'patient_id' => $patient->id,
                    'dentist_id' => $dentist->id,
                    'starts_at' => $startsAt->utc(),
                    'ends_at' => $startsAt->addHour()->utc(),
                    'planned_treatment' => $treatment->name,
                    'administrative_notes' => $marker,
                    'status' => $status,
                    'created_by' => $receptionist->id,
                    'started_at' => $status === AppointmentStatus::Completed ? $startsAt->addMinutes(5)->utc() : null,
                    'completed_at' => $status === AppointmentStatus::Completed ? $startsAt->addMinutes(50)->utc() : null,
                    'cancelled_at' => $status === AppointmentStatus::Cancelled ? $startsAt->subDay()->utc() : null,
                    'cancelled_by' => $status === AppointmentStatus::Cancelled ? $receptionist->id : null,
                    'cancellation_reason' => $status === AppointmentStatus::Cancelled ? 'DEMO: cancelación simulada.' : null,
                ]);

                $this->recordHistoricalAppointmentEvents($appointment, $receptionist);
            }

            $appointments[$key] = $appointment;
        }

        return $appointments;
    }

    private function recordHistoricalAppointmentEvents(Appointment $appointment, User $actor): void
    {
        $appointment->events()->create([
            'user_id' => $actor->id,
            'event_type' => AppointmentEvent::CREATED,
            'to_status' => AppointmentStatus::Scheduled->value,
        ]);

        if ($appointment->status === AppointmentStatus::Completed) {
            $appointment->events()->create([
                'user_id' => $actor->id,
                'event_type' => AppointmentEvent::STARTED,
                'from_status' => AppointmentStatus::Scheduled->value,
                'to_status' => AppointmentStatus::InTreatment->value,
            ]);
        }

        $appointment->events()->create([
            'user_id' => $actor->id,
            'event_type' => $appointment->status === AppointmentStatus::Completed ? AppointmentEvent::COMPLETED : AppointmentEvent::CANCELLED,
            'from_status' => $appointment->status === AppointmentStatus::Completed ? AppointmentStatus::InTreatment->value : AppointmentStatus::Scheduled->value,
            'to_status' => $appointment->status->value,
            'reason' => $appointment->cancellation_reason,
        ]);
    }

    private function createProcedure(Appointment $appointment, Treatment $treatment, User $dentistUser): PatientTreatment
    {
        $procedure = PatientTreatment::withTrashed()
            ->where('appointment_id', $appointment->id)
            ->where('treatment_id', $treatment->id)
            ->first();

        if ($procedure !== null) {
            if ($procedure->trashed() || $procedure->patient_id !== $appointment->patient_id
                || $procedure->dentist_id !== $appointment->dentist_id
                || ! str_starts_with($procedure->procedure_description, 'DEMO F18')) {
                throw new RuntimeException('Existe un procedimiento incompatible con la cita DEMO.');
            }

            return $procedure;
        }

        $procedure = PatientTreatment::query()->create([
            'patient_id' => $appointment->patient_id,
            'dentist_id' => $appointment->dentist_id,
            'treatment_id' => $treatment->id,
            'appointment_id' => $appointment->id,
            'registered_by' => $dentistUser->id,
            'performed_at' => $appointment->starts_at->addMinutes(30),
            'procedure_description' => 'DEMO F18: procedimiento simulado de '.$treatment->name.'.',
            'clinical_notes' => 'DEMO F18: caso académico ficticio, sin diagnóstico real.',
            'price_applied' => $treatment->price,
        ]);
        PatientTreatmentEvent::query()->create([
            'patient_treatment_id' => $procedure->id,
            'user_id' => $dentistUser->id,
            'event_type' => 'created',
            'changes' => ['price_applied' => ['new' => $procedure->price_applied]],
        ]);

        return $procedure;
    }

    private function createPendingPlan(Patient $patient, Dentist $dentist, Treatment $treatment, User $dentistUser): void
    {
        $title = 'DEMO F18 - Plan de tratamiento de conducto';
        $plan = TreatmentPlan::query()->firstOrCreate(['title' => $title], [
            'patient_id' => $patient->id,
            'dentist_id' => $dentist->id,
            'created_by' => $dentistUser->id,
            'status' => TreatmentPlanStatus::Pending,
            'description' => 'DEMO F18: planificación ficticia sin diagnóstico real.',
        ]);

        if ($plan->patient_id !== $patient->id || $plan->dentist_id !== $dentist->id) {
            throw new RuntimeException('Existe un plan incompatible con el identificador DEMO.');
        }

        $plan->items()->firstOrCreate(['treatment_id' => $treatment->id], [
            'quantity' => 1,
            'estimated_unit_price' => $treatment->price,
            'status' => TreatmentPlanStatus::Pending,
            'sort_order' => 1,
        ]);
    }

    private function createInvoice(Patient $patient, PatientTreatment $procedure, User $actor): DentalInvoice
    {
        $detail = DentalInvoiceDetail::query()->where('patient_treatment_id', $procedure->id)->first();

        if ($detail !== null) {
            $invoice = $detail->invoice;

            if ($invoice->patient_id !== $patient->id || ! str_starts_with((string) $invoice->number, 'DEMO-DFA-')
                || $invoice->status === DentalInvoiceStatus::Cancelled) {
                throw new RuntimeException('Existe una factura incompatible con el procedimiento DEMO.');
            }

            return $invoice;
        }

        $invoice = (new CreateDentalInvoiceDraft)->handle($patient, [$procedure->id], $actor);
        $invoice = (new IssueDentalInvoice)->handle($invoice, $actor);
        $invoice->update(['number' => 'DEMO-'.$invoice->number]);

        return $invoice;
    }

    private function createPayment(DentalInvoice $invoice, string $amount, string $key, User $actor): void
    {
        $existingPayment = DentalPayment::query()->where('idempotency_key', $key)->first();

        if ($existingPayment !== null) {
            if ($existingPayment->dental_invoice_id !== $invoice->id
                || ! str_starts_with($existingPayment->number, 'DEMO-DREC-')
                || Money::toCents($existingPayment->amount) !== Money::toCents($amount)
                || $existingPayment->status !== DentalPaymentStatus::Registered) {
                throw new RuntimeException('Existe un pago incompatible con el identificador DEMO.');
            }

            return;
        }

        $payment = (new RegisterDentalPayment)->handle($invoice, [
            'amount' => $amount,
            'method' => PaymentMethod::Cash->value,
            'payment_date' => today()->toDateString(),
            'idempotency_key' => $key,
            'reference' => 'DEMO F18',
            'notes' => 'DEMO F18: comprobante académico sin validez fiscal.',
        ], $actor);
        $payment->update(['number' => 'DEMO-'.$payment->number]);
    }
}
