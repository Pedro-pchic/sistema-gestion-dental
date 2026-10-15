<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\DentalInvoiceStatus;
use App\Models\Announcement;
use App\Models\Appointment;
use App\Models\AppointmentRequest;
use App\Models\AuditLog;
use App\Models\DentalInvoice;
use App\Models\DentalPayment;
use App\Models\Patient;
use App\Models\PatientTreatment;
use App\Models\Role;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuditLogManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_audit_records_domain_changes_without_copying_personal_or_clinical_content(): void
    {
        $admin = User::factory()->for(Role::factory()->create(['name' => Role::ADMINISTRATOR]))->create(['is_active' => true]);
        $this->actingAs($admin);
        $patient = Patient::factory()->create(['email' => 'private@example.com']);
        $patient->update(['phone' => '55551234']);
        $appointment = Appointment::factory()->for($patient)->create();
        $appointment->update(['status' => AppointmentStatus::Confirmed]);
        $treatment = Treatment::factory()->create();
        $booking = AppointmentRequest::query()->create([
            'treatment_id' => $treatment->id,
            'dentist_id' => $appointment->dentist_id,
            'starts_at' => now()->addDays(4),
            'ends_at' => now()->addDays(4)->addHour(),
            'name' => 'Persona confidencial',
            'phone' => '55551234',
            'email' => 'private@example.com',
            'status' => 'pending',
        ]);
        $booking->update(['status' => 'confirmed', 'appointment_id' => $appointment->id, 'patient_id' => $patient->id]);
        $procedure = PatientTreatment::factory()->for($patient)->create(['clinical_notes' => 'Nota médica secreta']);
        $procedure->update(['clinical_notes' => 'Diagnóstico secreto actualizado']);
        $invoice = DentalInvoice::factory()->for($patient)->create();
        $invoice->update(['status' => DentalInvoiceStatus::Issued, 'total' => '100.00']);
        $payment = DentalPayment::factory()->for($invoice, 'invoice')->create(['notes' => 'Tarjeta confidencial']);
        $announcement = Announcement::query()->create(['title' => 'Anuncio', 'description' => 'Texto público', 'published_on' => today(), 'is_published' => false]);
        $announcement->update(['is_published' => true]);

        $this->assertDatabaseHas('audit_logs', ['module' => 'patients', 'action' => 'created', 'auditable_id' => $patient->id]);
        $this->assertDatabaseHas('audit_logs', ['module' => 'appointments', 'action' => 'confirmed', 'auditable_id' => $appointment->id]);
        $statusLog = AuditLog::query()->where('module', 'appointments')->where('action', 'confirmed')->where('auditable_id', $appointment->id)->firstOrFail();
        $this->assertSame('scheduled', $statusLog->metadata['status_before']);
        $this->assertSame('confirmed', $statusLog->metadata['status_after']);
        $this->assertDatabaseHas('audit_logs', ['module' => 'appointment_requests', 'action' => 'confirmed', 'auditable_id' => $booking->id]);
        $this->assertDatabaseHas('audit_logs', ['module' => 'finances', 'action' => 'issued', 'auditable_id' => $invoice->id]);
        $this->assertDatabaseHas('audit_logs', ['module' => 'finances', 'action' => 'created', 'auditable_id' => $payment->id]);
        $this->assertDatabaseHas('audit_logs', ['module' => 'public_site', 'action' => 'published', 'auditable_id' => $announcement->id]);
        $this->assertDatabaseHas('audit_logs', ['module' => 'clinical_records', 'action' => 'updated', 'auditable_id' => $procedure->id]);
        $this->assertSame($admin->id, AuditLog::where('module', 'clinical_records')->where('action', 'updated')->firstOrFail()->actor_user_id);
        $storedMetadata = AuditLog::query()->pluck('metadata')->implode(' ');
        $this->assertStringNotContainsString('Nota médica secreta', $storedMetadata);
        $this->assertStringNotContainsString('Diagnóstico secreto actualizado', $storedMetadata);
        $this->assertStringNotContainsString('private@example.com', $storedMetadata);
        $this->assertStringNotContainsString('Tarjeta confidencial', $storedMetadata);
    }

    public function test_only_administrator_with_audit_permission_can_filter_and_page_read_only_events(): void
    {
        $admin = User::factory()->for(Role::factory()->create(['name' => Role::ADMINISTRATOR]))->create(['is_active' => true]);
        $dentist = User::factory()->for(Role::factory()->create(['name' => Role::DENTIST]))->create(['is_active' => true]);
        $this->actingAs($admin);
        for ($index = 0; $index < 30; $index++) {
            AuditLog::record('security', 'test_event');
        }
        AuditLog::record('finances', 'payment_recorded');

        $this->get(route('audit.index', ['module' => 'security', 'action' => 'test_event']))
            ->assertOk()->assertViewHas('logs', fn ($logs) => $logs->total() === 30 && $logs->count() === 25);
        $this->get(route('audit.index', ['module' => 'finances', 'search' => 'payment']))
            ->assertOk()->assertViewHas('logs', fn ($logs) => $logs->total() === 1);
        $this->get(route('audit.index', ['module' => 'invalid']))->assertSessionHasErrors('module');
        $log = AuditLog::where('action', 'payment_recorded')->firstOrFail();
        $this->get(route('audit.show', $log))->assertOk()->assertSeeText('payment_recorded');
        $this->post(route('audit.show', $log))->assertStatus(405);
        $this->actingAs($dentist)->get(route('audit.index'))->assertForbidden();
        $this->actingAs($dentist)->get(route('audit.show', $log))->assertForbidden();
    }

    public function test_audit_event_rolls_back_with_a_critical_financial_change(): void
    {
        $admin = User::factory()->for(Role::factory()->create(['name' => Role::ADMINISTRATOR]))->create(['is_active' => true]);
        $this->actingAs($admin);
        $invoice = DentalInvoice::factory()->create();
        $before = AuditLog::count();

        try {
            DB::transaction(function () use ($invoice): void {
                $invoice->update(['status' => DentalInvoiceStatus::Issued]);
                throw new \RuntimeException('Abortar operación');
            });
        } catch (\RuntimeException) {
        }

        $this->assertSame(DentalInvoiceStatus::Draft, $invoice->fresh()->status);
        $this->assertSame($before, AuditLog::count());
    }

    public function test_automatic_change_has_a_system_actor(): void
    {
        $patient = Patient::factory()->create();
        $log = AuditLog::query()->where('module', 'patients')->where('auditable_id', $patient->id)->firstOrFail();

        $this->assertNull($log->actor_user_id);
        $this->assertSame('Sistema', $log->actor_role);
    }
}
