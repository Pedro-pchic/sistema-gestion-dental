<?php

namespace Tests\Feature;

use App\Contracts\SmsSender;
use App\Enums\AppointmentReminderStatus;
use App\Enums\AppointmentStatus;
use App\Jobs\SendAppointmentReminder;
use App\Models\Appointment;
use App\Models\AppointmentReminder;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Notifications\AppointmentReminderNotification;
use App\Services\AppointmentReminderScheduler;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;
use LogicException;
use Mockery;
use RuntimeException;
use Symfony\Component\Mailer\Exception\UnexpectedResponseException;
use Tests\TestCase;

class AppointmentReminderTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_dispatches_once_for_consented_scheduled_and_confirmed_appointments(): void
    {
        $this->freezeTime();
        config(['appointment_reminders.email.enabled' => true]);
        Queue::fake([SendAppointmentReminder::class]);

        $consentingPatient = $this->consentingPatient();
        $this->appointment($consentingPatient, AppointmentStatus::Scheduled);
        $this->appointment($consentingPatient, AppointmentStatus::Confirmed);
        $this->appointment($consentingPatient, AppointmentStatus::Cancelled);
        $this->appointment($consentingPatient, AppointmentStatus::Completed);
        $this->appointment($consentingPatient, AppointmentStatus::InTreatment);
        $this->appointment($this->consentingPatient(['email_reminders_enabled' => false]), AppointmentStatus::Scheduled);

        $scheduler = $this->app->make(AppointmentReminderScheduler::class);

        $dispatched = $scheduler->dispatchDueReminders();
        $this->assertSame(2, $dispatched);
        $this->assertSame(0, $scheduler->dispatchDueReminders());

        Queue::assertPushed(SendAppointmentReminder::class, 2);
        $this->assertDatabaseCount('appointment_reminders', 2);
        $this->assertDatabaseCount('appointment_reminder_attempts', 0);
    }

    public function test_command_queues_due_reminders_without_sending_them(): void
    {
        $this->freezeTime();
        config(['appointment_reminders.email.enabled' => true]);
        Queue::fake([SendAppointmentReminder::class]);
        $this->appointment($this->consentingPatient());

        $this->artisan('appointments:dispatch-reminders')
            ->expectsOutput('Recordatorios encolados: 1.')
            ->assertSuccessful();

        Queue::assertPushed(SendAppointmentReminder::class, 1);
        $this->assertDatabaseCount('appointment_reminders', 1);
        $this->assertDatabaseCount('appointment_reminder_attempts', 0);
    }

    public function test_skips_a_queued_reminder_after_cancellation_or_rescheduling(): void
    {
        $this->freezeTime();
        config(['appointment_reminders.email.enabled' => true]);
        Queue::fake([SendAppointmentReminder::class]);

        $rescheduledAppointment = $this->appointment($this->consentingPatient());
        $cancelledAppointment = $this->appointment($this->consentingPatient());
        $scheduler = $this->app->make(AppointmentReminderScheduler::class);

        $this->assertSame(2, $scheduler->dispatchDueReminders());
        $reminders = AppointmentReminder::query()->orderBy('id')->get();
        $staleReminder = $reminders->first();
        $cancelledReminder = $reminders->last();

        $rescheduledAppointment->update([
            'starts_at' => now('UTC')->addHours(20),
            'ends_at' => now('UTC')->addHours(21),
        ]);
        $cancelledAppointment->update(['status' => AppointmentStatus::Cancelled]);
        $this->assertSame(1, $scheduler->dispatchDueReminders());

        (new SendAppointmentReminder((int) $staleReminder->getKey()))->handle();
        (new SendAppointmentReminder((int) $cancelledReminder->getKey()))->handle();

        $this->assertDatabaseHas('appointment_reminders', [
            'id' => $staleReminder->getKey(),
            'status' => AppointmentReminderStatus::Superseded->value,
            'attempts' => 0,
        ]);
        $this->assertDatabaseHas('appointment_reminders', [
            'id' => $cancelledReminder->getKey(),
            'status' => AppointmentReminderStatus::Superseded->value,
            'attempts' => 0,
        ]);
        $this->assertDatabaseCount('appointment_reminders', 3);
        $this->assertDatabaseCount('appointment_reminder_attempts', 0);
    }

    public function test_schedules_a_reminder_at_the_configured_24_hour_boundary(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-12 10:00:00', 'UTC'));
        config(['appointment_reminders.email.enabled' => true]);
        Queue::fake([SendAppointmentReminder::class]);
        $startsAt = now('UTC')->addHours(24);
        $this->appointment($this->consentingPatient(), AppointmentStatus::Scheduled, [
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHour(),
        ]);

        $this->assertSame(1, $this->app->make(AppointmentReminderScheduler::class)->dispatchDueReminders());
        Queue::assertPushed(SendAppointmentReminder::class, 1);
    }

    public function test_skips_a_queued_email_if_the_patient_revokes_consent_before_delivery(): void
    {
        $this->freezeTime();
        config([
            'appointment_reminders.email.enabled' => true,
            'mail.default' => 'array',
        ]);
        $patient = $this->consentingPatient();
        $appointment = $this->appointment($patient);
        $reminder = $this->queuedReminder($appointment);
        $patient->update([
            'email_reminders_enabled' => false,
            'email_reminders_revoked_at' => now('UTC'),
        ]);

        (new SendAppointmentReminder((int) $reminder->getKey()))->handle();

        $this->assertDatabaseHas('appointment_reminders', [
            'id' => $reminder->getKey(),
            'status' => AppointmentReminderStatus::Skipped->value,
            'attempts' => 0,
        ]);
        $this->assertDatabaseCount('appointment_reminder_attempts', 0);
    }

    public function test_sends_a_private_email_and_records_the_delivery_attempt(): void
    {
        $this->freezeTime();
        config([
            'appointment_reminders.email.enabled' => true,
            'mail.default' => 'array',
        ]);

        $patient = $this->consentingPatient();
        $appointment = $this->appointment($patient, AppointmentStatus::Confirmed, [
            'planned_treatment' => 'Tratamiento reservado',
            'administrative_notes' => 'Nota interna confidencial',
        ]);
        $reminder = $this->queuedReminder($appointment);
        $notification = new AppointmentReminderNotification($appointment, 'email');
        $content = $notification->toMail($patient)->render();

        (new SendAppointmentReminder((int) $reminder->getKey()))->handle();

        $this->assertSame('Recordatorio de su cita odontológica', $notification->toMail($patient)->subject);
        $this->assertStringContainsString('Le recordamos su cita', $content);
        $this->assertStringNotContainsString('Tratamiento reservado', $content);
        $this->assertStringNotContainsString('Nota interna confidencial', $content);
        $this->assertDatabaseHas('appointment_reminders', [
            'id' => $reminder->getKey(),
            'status' => AppointmentReminderStatus::Sent->value,
            'attempts' => 1,
        ]);
        $this->assertDatabaseHas('appointment_reminder_attempts', [
            'appointment_reminder_id' => $reminder->getKey(),
            'attempt_number' => 1,
            'result' => 'sent',
        ]);
    }

    public function test_retries_sms_failures_with_bounded_backoff_and_stops_after_three_attempts(): void
    {
        $this->freezeTime();
        config([
            'appointment_reminders.sms.enabled' => true,
            'appointment_reminders.sms.driver' => 'test',
        ]);
        $smsSender = Mockery::mock(SmsSender::class);
        $smsSender->shouldReceive('isAvailable')->andReturn(true);
        $smsSender->shouldReceive('send')->times(3)->andThrow(new RuntimeException('Sensitive provider response'));
        $this->app->instance(SmsSender::class, $smsSender);

        $appointment = $this->appointment($this->consentingPatient([
            'sms_reminders_enabled' => true,
            'sms_reminders_consent_at' => now('UTC'),
            'phone' => '+502 5555-1234',
        ]));
        $reminder = $this->queuedReminder($appointment, 'sms');

        (new SendAppointmentReminder((int) $reminder->getKey()))->handle();
        $reminder->refresh();
        $this->assertSame(AppointmentReminderStatus::Failed, $reminder->status);
        $this->assertSame(1, $reminder->attempts);
        $this->assertSame(now('UTC')->addMinutes(15)->toDateTimeString(), $reminder->next_attempt_at->toDateTimeString());

        Queue::fake([SendAppointmentReminder::class]);
        $this->travel(16)->minutes();
        $this->assertSame(1, $this->app->make(AppointmentReminderScheduler::class)->dispatchDueReminders());
        (new SendAppointmentReminder((int) $reminder->getKey()))->handle();
        $reminder->refresh();
        $this->assertSame(2, $reminder->attempts);
        $this->assertSame(now('UTC')->addMinutes(60)->toDateTimeString(), $reminder->next_attempt_at->toDateTimeString());

        $this->travel(61)->minutes();
        $this->assertSame(1, $this->app->make(AppointmentReminderScheduler::class)->dispatchDueReminders());
        (new SendAppointmentReminder((int) $reminder->getKey()))->handle();
        $reminder->refresh();

        $this->assertSame(AppointmentReminderStatus::Failed, $reminder->status);
        $this->assertSame(3, $reminder->attempts);
        $this->assertNull($reminder->next_attempt_at);
        $this->assertDatabaseCount('appointment_reminder_attempts', 3);
        $this->assertSame(0, $this->app->make(AppointmentReminderScheduler::class)->dispatchDueReminders());
    }

    public function test_keeps_sms_disabled_when_no_provider_is_configured(): void
    {
        $this->freezeTime();
        config([
            'appointment_reminders.email.enabled' => false,
            'appointment_reminders.sms.enabled' => true,
            'appointment_reminders.sms.driver' => 'unavailable',
        ]);

        $this->appointment($this->consentingPatient([
            'sms_reminders_enabled' => true,
            'phone' => '+502 5555-1234',
        ]));

        $this->assertSame(0, $this->app->make(AppointmentReminderScheduler::class)->dispatchDueReminders());
        $this->assertDatabaseCount('appointment_reminders', 0);
    }

    public function test_enabled_preference_without_current_consent_never_queues_or_sends(): void
    {
        $this->freezeTime();
        config(['appointment_reminders.email.enabled' => true, 'mail.default' => 'array']);
        Queue::fake([SendAppointmentReminder::class]);
        $patient = $this->consentingPatient(['email_reminders_consent_at' => null]);
        $appointment = $this->appointment($patient);
        $this->appointment($this->consentingPatient(['email' => 'invalid-email']));
        $this->appointment($this->consentingPatient(['email_reminders_revoked_at' => now('UTC')]));
        $this->appointment($this->consentingPatient(['is_active' => false]));

        $this->assertSame(0, $this->app->make(AppointmentReminderScheduler::class)->dispatchDueReminders());
        Queue::assertNotPushed(SendAppointmentReminder::class);

        $reminder = $this->queuedReminder($appointment);
        (new SendAppointmentReminder((int) $reminder->getKey()))->handle();
        $this->assertSame(AppointmentReminderStatus::Skipped, $reminder->fresh()->status);
        $this->assertDatabaseCount('appointment_reminder_attempts', 0);
    }

    public function test_permanent_sms_failure_is_not_retried_or_exposed(): void
    {
        $this->freezeTime();
        config([
            'appointment_reminders.sms.enabled' => true,
            'appointment_reminders.sms.driver' => 'test',
        ]);
        $smsSender = Mockery::mock(SmsSender::class);
        $smsSender->shouldReceive('isAvailable')->andReturn(true);
        $smsSender->shouldReceive('send')->once()->andThrow(new LogicException('Private provider response'));
        $this->app->instance(SmsSender::class, $smsSender);

        $appointment = $this->appointment($this->consentingPatient([
            'sms_reminders_enabled' => true,
            'sms_reminders_consent_at' => now('UTC'),
            'phone' => '+502 5555-1234',
        ]));
        $reminder = $this->queuedReminder($appointment, 'sms');

        (new SendAppointmentReminder((int) $reminder->getKey()))->handle();

        $reminder->refresh();
        $this->assertSame(AppointmentReminderStatus::Failed, $reminder->status);
        $this->assertNull($reminder->next_attempt_at);
        $this->assertStringNotContainsString('Private provider response', $reminder->last_error);
        $this->assertSame(0, $this->app->make(AppointmentReminderScheduler::class)->dispatchDueReminders());
    }

    public function test_permanent_smtp_response_does_not_schedule_another_attempt(): void
    {
        $this->freezeTime();
        config([
            'appointment_reminders.sms.enabled' => true,
            'appointment_reminders.sms.driver' => 'test',
        ]);
        $smsSender = Mockery::mock(SmsSender::class);
        $smsSender->shouldReceive('isAvailable')->andReturn(true);
        $smsSender->shouldReceive('send')->once()->andThrow(new UnexpectedResponseException('Private SMTP response', 550));
        $this->app->instance(SmsSender::class, $smsSender);
        $appointment = $this->appointment($this->consentingPatient([
            'sms_reminders_enabled' => true,
            'sms_reminders_consent_at' => now('UTC'),
            'phone' => '+502 5555-1234',
        ]));
        $reminder = $this->queuedReminder($appointment, 'sms');

        (new SendAppointmentReminder((int) $reminder->getKey()))->handle();

        $this->assertSame(AppointmentReminderStatus::Failed, $reminder->fresh()->status);
        $this->assertNull($reminder->fresh()->next_attempt_at);
        $this->assertStringNotContainsString('Private SMTP response', $reminder->fresh()->last_error);
    }

    public function test_administrative_center_filters_and_paginates_without_exposing_errors_or_clinical_data(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-12 10:00:00', 'UTC'));
        $role = Role::query()->firstOrCreate(['name' => Role::ADMINISTRATOR]);
        $administrator = User::factory()->for($role)->create(['is_active' => true]);
        $patient = $this->consentingPatient();
        $appointment = $this->appointment($patient, AppointmentStatus::Scheduled, [
            'planned_treatment' => 'Tratamiento reservado',
        ]);
        $reminder = $this->queuedReminder($appointment);
        $reminder->update(['status' => AppointmentReminderStatus::Failed, 'last_error' => 'Private provider response']);
        $this->queuedReminder($this->appointment($patient));
        $otherChannel = $this->queuedReminder($this->appointment($patient), 'sms');
        $otherChannel->update(['status' => AppointmentReminderStatus::Failed]);
        $previousStart = CarbonImmutable::parse('2026-10-11 12:00:00', 'UTC');
        $oldAppointment = $this->appointment($patient, AppointmentStatus::Scheduled, [
            'starts_at' => $previousStart,
            'ends_at' => $previousStart->addHour(),
        ]);
        $oldReminder = $this->queuedReminder($oldAppointment);
        $oldReminder->update(['status' => AppointmentReminderStatus::Failed]);

        $response = $this->actingAs($administrator)->get(route('recordatorios.index', [
            'from' => '2026-10-12',
            'to' => '2026-10-12',
            'status' => 'failed',
            'channel' => 'email',
        ]))->assertOk()
            ->assertSeeText('Falló la entrega.')
            ->assertDontSeeText('Private provider response')
            ->assertDontSeeText('Tratamiento reservado');
        $this->assertSame(1, $response->viewData('reminders')->total());

        foreach (range(1, 20) as $number) {
            $this->queuedReminder($this->appointment($patient));
        }
        $response = $this->actingAs($administrator)->get(route('recordatorios.index'))->assertOk()
            ->assertSee('page=2', false);
        $this->assertSame(24, $response->viewData('reminders')->total());

        $this->actingAs($administrator)->get(route('recordatorios.index', [
            'from' => '2026-10-13', 'to' => '2026-10-12',
        ]))->assertSessionHasErrors('to');
        $this->actingAs($administrator)->get(route('recordatorios.index', ['channel' => 'unknown']))
            ->assertSessionHasErrors('channel');
    }

    public function test_only_active_administrators_and_receptionists_can_view_reminders(): void
    {
        $administrator = User::factory()->for(Role::factory()->create(['name' => Role::ADMINISTRATOR]))->create(['is_active' => true]);
        $receptionist = User::factory()->for(Role::factory()->create(['name' => Role::RECEPTIONIST]))->create(['is_active' => true]);
        $dentist = User::factory()->for(Role::factory()->create(['name' => Role::DENTIST]))->create(['is_active' => true]);

        $this->actingAs($administrator)->get(route('recordatorios.index'))->assertOk();
        $this->actingAs($receptionist)->get(route('recordatorios.index'))->assertOk();
        $this->actingAs($dentist)->get(route('recordatorios.index'))->assertForbidden();
        $receptionist->update(['is_active' => false]);
        $this->actingAs($receptionist)->get(route('recordatorios.index'))->assertRedirect();
    }

    private function consentingPatient(array $attributes = []): Patient
    {
        return Patient::factory()->create(array_merge([
            'email_reminders_enabled' => true,
            'email_reminders_consent_at' => now('UTC'),
            'sms_reminders_enabled' => false,
        ], $attributes));
    }

    private function appointment(
        Patient $patient,
        AppointmentStatus $status = AppointmentStatus::Scheduled,
        array $attributes = [],
    ): Appointment {
        $startsAt = now('UTC')->addHours(23);

        return Appointment::factory()->for($patient)->create(array_merge([
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHour(),
            'status' => $status,
        ], $attributes));
    }

    private function queuedReminder(Appointment $appointment, string $channel = 'email'): AppointmentReminder
    {
        return AppointmentReminder::query()->create([
            'appointment_id' => $appointment->getKey(),
            'patient_id' => $appointment->patient_id,
            'channel' => $channel,
            'appointment_starts_at' => $appointment->starts_at,
            'scheduled_at' => $appointment->starts_at->copy()->subHours(24),
            'status' => AppointmentReminderStatus::Queued,
            'attempts' => 0,
        ]);
    }
}
