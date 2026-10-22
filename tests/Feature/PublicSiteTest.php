<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Announcement;
use App\Models\Appointment;
use App\Models\AppointmentRequest;
use App\Models\Dentist;
use App\Models\Patient;
use App\Models\PublicSiteSetting;
use App\Models\Role;
use App\Models\Treatment;
use App\Models\User;
use App\Services\AppointmentScheduler;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_public_landing_only_shows_authorized_catalog_and_current_announcements(): void
    {
        $visible = Treatment::factory()->create(['name' => 'Limpieza visible', 'is_public' => true, 'show_public_price' => false]);
        Treatment::factory()->create(['name' => 'Tratamiento interno', 'is_public' => false]);
        $this->dentist();
        $today = today(config('appointments.timezone'));
        Announcement::query()->create(['title' => 'Noticia vigente', 'description' => 'Abierto', 'published_on' => $today, 'is_published' => true]);
        Announcement::query()->create(['title' => 'Noticia vencida', 'description' => 'Cerrado', 'published_on' => $today->copy()->subDays(2), 'expires_on' => $today->copy()->subDay(), 'is_published' => true]);
        Announcement::query()->create(['title' => 'Noticia futura', 'description' => 'Pendiente', 'published_on' => $today->copy()->addDay(), 'is_published' => true]);
        Announcement::query()->create(['title' => 'Borrador privado', 'description' => 'Oculto', 'published_on' => $today, 'is_published' => false]);

        $this->get(route('public.home'))->assertOk()
            ->assertSeeText('Chimuelo')
            ->assertSee('logo.png')
            ->assertSeeText($visible->name)
            ->assertSeeText('Noticia vigente')
            ->assertDontSeeText('Tratamiento interno')
            ->assertDontSeeText('Noticia vencida')
            ->assertDontSeeText('Noticia futura')
            ->assertDontSeeText('Borrador privado')
            ->assertDontSeeText($visible->price);
    }

    public function test_public_request_checks_validation_confirmed_slots_and_duplicates(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        [$dentist, $treatment, $date] = $this->bookableCatalog();
        $payload = $this->payload($dentist, $treatment, $date);

        $this->post(route('public.appointments.store'), [])->assertSessionHasErrors(['treatment_id', 'dentist_id', 'date', 'time', 'name', 'phone', 'email']);
        $this->getJson(route('public.slots', ['dentist_id' => $dentist->id, 'treatment_id' => $treatment->id, 'date' => $date->toDateString()]))
            ->assertOk()->assertJsonFragment(['09:00']);
        $this->post(route('public.appointments.store'), $payload)->assertRedirect();
        $this->assertDatabaseCount('appointment_requests', 1);
        $this->post(route('public.appointments.store'), $payload)->assertRedirect()->assertSessionHasErrors('time');
        $this->getJson(route('public.slots', ['dentist_id' => $dentist->id, 'treatment_id' => $treatment->id, 'date' => $date->toDateString()]))
            ->assertOk()->assertJsonFragment(['09:00']);

        $booking = AppointmentRequest::query()->firstOrFail();
        $booking->update(['status' => 'rejected', 'duplicate_key' => null]);
        $patient = Patient::factory()->create();
        Appointment::factory()->for($patient)->for($dentist)->create([
            'starts_at' => $booking->starts_at,
            'ends_at' => $booking->ends_at,
        ]);
        $this->assertFalse(app(AppointmentScheduler::class)->isAvailable($dentist->id, $booking->starts_at, $booking->ends_at));
        $this->getJson(route('public.slots', ['dentist_id' => $dentist->id, 'treatment_id' => $treatment->id, 'date' => $date->toDateString()]))
            ->assertJsonPath('times.0.available', false);
        $this->post(route('public.appointments.store'), $payload)->assertRedirect()->assertSessionHasErrors('time');
        $this->assertDatabaseCount('appointment_requests', 1);
    }

    public function test_default_daily_hours_and_treatment_duration_limit_start_times(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        $dentist = $this->dentist();
        $treatment = Treatment::factory()->create(['is_public' => true, 'estimated_duration_minutes' => 90]);
        $timezone = config('appointments.timezone');

        foreach ([
            ['Monday', '08:00', '17:30', '18:00'],
            ['Saturday', '10:00', '15:30', '16:00'],
            ['Sunday', '10:00', '11:30', '12:00'],
        ] as [$weekday, $first, $last, $tooLate]) {
            $date = CarbonImmutable::today($timezone)->next($weekday);
            $url = route('public.slots', ['dentist_id' => $dentist->id, 'treatment_id' => $treatment->id, 'date' => $date->toDateString()]);
            $this->getJson($url)->assertOk()->assertJsonPath('slots.0', $first)->assertJsonFragment([$last])->assertDontSee($tooLate);
            $this->post(route('public.appointments.store'), [
                ...$this->payload($dentist, $treatment, $date), 'time' => $tooLate, 'email' => "late-$weekday@example.test",
            ])->assertSessionHasErrors('time');
        }
    }

    public function test_calendar_excludes_past_dates_and_marks_only_available_days(): void
    {
        [$dentist, $treatment, $bookableDate] = $this->bookableCatalog();
        $today = CarbonImmutable::today(config('appointments.timezone'));
        $past = $today->startOfMonth()->subDay();
        $monthUrl = route('public.slots', ['dentist_id' => $dentist->id, 'treatment_id' => $treatment->id, 'month' => $past->format('Y-m')]);

        $this->getJson($monthUrl)->assertOk()->assertJsonPath('days.'.$past->toDateString(), false);
        $this->getJson(route('public.slots', ['dentist_id' => $dentist->id, 'treatment_id' => $treatment->id, 'month' => $bookableDate->format('Y-m')]))
            ->assertOk()->assertJsonPath('days.'.$bookableDate->toDateString(), true);
        $this->getJson(route('public.slots', ['dentist_id' => $dentist->id, 'treatment_id' => $treatment->id, 'date' => $past->toDateString()]))
            ->assertOk()->assertJsonPath('slots', []);
        $this->post(route('public.appointments.store'), $this->payload($dentist, $treatment, $past))->assertSessionHasErrors('time');
        $this->getJson(route('public.slots', ['dentist_id' => $dentist->id, 'treatment_id' => $treatment->id, 'date' => $today->addDays(61)->toDateString()]))
            ->assertOk()->assertJsonPath('slots', []);
        $this->getJson(route('public.slots', ['dentist_id' => $dentist->id, 'treatment_id' => $treatment->id, 'month' => $today->format('Y-m'), 'date' => $today->toDateString()]))
            ->assertUnprocessable();
    }

    public function test_cancelled_appointments_release_their_slots_without_exposing_patient_data(): void
    {
        [$dentist, $treatment, $date] = $this->bookableCatalog();
        $patient = Patient::factory()->create(['first_name' => 'Privado', 'email' => 'secret@example.test']);
        $start = CarbonImmutable::parse($date->toDateString().' 09:00', config('appointments.timezone'))->utc();
        $appointment = Appointment::factory()->for($patient)->for($dentist)->create([
            'starts_at' => $start, 'ends_at' => $start->addHour(), 'status' => AppointmentStatus::Confirmed,
        ]);
        $url = route('public.slots', ['dentist_id' => $dentist->id, 'treatment_id' => $treatment->id, 'date' => $date->toDateString()]);

        $this->getJson($url)->assertOk()->assertJsonFragment(['time' => '09:00', 'available' => false])
            ->assertDontSee('Privado')->assertDontSee('secret@example.test');
        $appointment->update(['status' => AppointmentStatus::Cancelled]);
        $this->getJson($url)->assertOk()->assertJsonFragment(['time' => '09:00', 'available' => true]);
    }

    public function test_two_pending_requests_can_share_a_slot_but_only_one_can_be_confirmed(): void
    {
        [$dentist, $treatment, $date] = $this->bookableCatalog();
        $payload = $this->payload($dentist, $treatment, $date);
        $this->post(route('public.appointments.store'), $payload)->assertRedirect();
        $this->post(route('public.appointments.store'), [
            ...$payload, 'name' => 'Beatriz Solís', 'email' => 'beatriz@example.test', 'phone' => '55556789',
        ])->assertRedirect();
        $this->assertDatabaseCount('appointment_requests', 2);
        $receptionist = $this->userWithRole(Role::RECEPTIONIST);
        [$first, $second] = AppointmentRequest::query()->orderBy('id')->get()->all();

        $this->actingAs($receptionist)->post(route('solicitudes-cita.confirm', $first))->assertRedirect();
        $this->actingAs($receptionist)->post(route('solicitudes-cita.confirm', $second))->assertSessionHasErrors('starts_at');
        $this->assertDatabaseCount('appointments', 1);
        $this->assertDatabaseHas('appointment_requests', ['id' => $first->id, 'status' => 'confirmed']);
        $this->assertDatabaseHas('appointment_requests', ['id' => $second->id, 'status' => 'pending']);
        $this->actingAs($receptionist)->get(route('citas.index'))->assertOk()->assertSeeText('Solicitudes web');
        $this->actingAs($receptionist)->get(route('solicitudes-cita.index', ['date' => $date->toDateString(), 'status' => 'pending', 'dentist_id' => $dentist->id]))
            ->assertOk()->assertSeeText('Beatriz Solís')->assertDontSeeText('Ana López');
    }

    public function test_administrator_can_edit_daily_hours_without_losing_existing_site_settings(): void
    {
        $settings = PublicSiteSetting::query()->create(['clinic_name' => 'Sonrisa Clara', 'booking_weekdays' => [1], 'booking_starts_at' => '09:00', 'booking_ends_at' => '17:00']);
        $administrator = $this->userWithRole(Role::ADMINISTRATOR);

        $this->actingAs($administrator)->put(route('configuracion-publica.update'), [
            'booking_hours' => [1 => ['start' => '08:30', 'end' => '18:30'], 2 => ['start' => '', 'end' => '']],
        ])->assertRedirect();
        $this->assertSame('Sonrisa Clara', $settings->fresh()->clinic_name);
        $this->assertSame([1 => ['start' => '08:30', 'end' => '18:30']], $settings->fresh()->bookingHours());
        $this->actingAs($administrator)->put(route('configuracion-publica.update'), [
            'booking_hours' => [1 => ['start' => '18:00', 'end' => '08:00']],
        ])->assertSessionHasErrors('booking_hours.1.end');
    }

    public function test_reception_confirms_a_request_without_creating_a_duplicate_patient(): void
    {
        [$dentist, $treatment, $date] = $this->bookableCatalog();
        $patient = Patient::factory()->create(['email' => 'ana@example.test', 'phone' => '55551234']);
        $this->post(route('public.appointments.store'), $this->payload($dentist, $treatment, $date))->assertRedirect();
        $booking = AppointmentRequest::query()->firstOrFail();
        $receptionist = $this->userWithRole(Role::RECEPTIONIST);

        $this->actingAs($receptionist)->post(route('solicitudes-cita.confirm', $booking))->assertSessionHasErrors('patient_id');
        $this->actingAs($receptionist)->post(route('solicitudes-cita.confirm', $booking), ['patient_id' => $patient->id])->assertRedirect();

        $this->assertDatabaseCount('patients', 1);
        $appointment = Appointment::query()->firstOrFail();
        $this->assertSame(AppointmentStatus::Confirmed, $appointment->status);
        $this->assertSame($patient->id, $appointment->patient_id);
        $this->assertDatabaseHas('appointment_requests', ['id' => $booking->id, 'status' => 'confirmed', 'appointment_id' => $appointment->id]);
        $this->assertDatabaseHas('appointment_request_events', ['appointment_request_id' => $booking->id, 'event_type' => 'confirmed', 'user_id' => $receptionist->id]);
    }

    public function test_private_requests_and_administration_require_authorized_roles(): void
    {
        [$dentist, $treatment, $date] = $this->bookableCatalog();
        $this->post(route('public.appointments.store'), $this->payload($dentist, $treatment, $date))->assertRedirect();
        $booking = AppointmentRequest::query()->firstOrFail();
        $this->get(route('solicitudes-cita.show', $booking))->assertRedirectToRoute('login');
        $this->get(route('anuncios.index'))->assertRedirectToRoute('login');
        $this->actingAs($this->userWithRole(Role::DENTIST))->get(route('solicitudes-cita.show', $booking))->assertForbidden();
        $this->get(route('configuracion-publica.edit'))->assertForbidden();
        $this->get(route('anuncios.index'))->assertForbidden();
        $this->get(route('public.home'))->assertDontSeeText('ana@example.test');
    }

    public function test_admin_can_publish_and_unpublish_an_announcement(): void
    {
        $admin = $this->userWithRole(Role::ADMINISTRATOR);
        $this->actingAs($admin)->post(route('anuncios.store'), [
            'title' => 'Archivo no permitido', 'description' => 'Texto',
            'published_on' => today(config('appointments.timezone'))->toDateString(),
            'sort_order' => 0, 'is_published' => 0,
            'image' => UploadedFile::fake()->create('script.svg', 2, 'image/svg+xml'),
        ])->assertSessionHasErrors('image');
        $this->actingAs($admin)->post(route('anuncios.store'), [
            'title' => 'Campaña', 'description' => 'Atención especial', 'published_on' => today(config('appointments.timezone'))->toDateString(),
            'sort_order' => 1, 'is_published' => 1,
        ])->assertRedirectToRoute('anuncios.index');
        $announcement = Announcement::query()->firstOrFail();
        $this->get(route('public.home'))->assertSeeText('Campaña');
        $this->get(route('anuncios.edit', $announcement))->assertOk()->assertSee('value="Campaña"', false);
        $this->put(route('anuncios.update', $announcement), [
            'title' => 'Campaña actualizada', 'description' => 'Atención especial',
            'published_on' => today(config('appointments.timezone'))->toDateString(),
            'sort_order' => 2, 'is_published' => 1,
        ])->assertRedirectToRoute('anuncios.index');
        $this->get(route('public.home'))->assertSeeText('Campaña actualizada');
        $this->patch(route('anuncios.toggle', $announcement))->assertRedirect();
        $this->get(route('public.home'))->assertDontSeeText('Campaña');
    }

    public function test_reception_cannot_confirm_a_slot_taken_after_the_public_request(): void
    {
        [$dentist, $treatment, $date] = $this->bookableCatalog();
        $this->post(route('public.appointments.store'), $this->payload($dentist, $treatment, $date))->assertRedirect();
        $booking = AppointmentRequest::query()->firstOrFail();
        $patient = Patient::factory()->create();
        Appointment::factory()->for($patient)->for($dentist)->create([
            'starts_at' => $booking->starts_at,
            'ends_at' => $booking->ends_at,
        ]);

        $this->actingAs($this->userWithRole(Role::RECEPTIONIST))
            ->post(route('solicitudes-cita.confirm', $booking))
            ->assertSessionHasErrors('starts_at');

        $this->assertDatabaseHas('appointment_requests', ['id' => $booking->id, 'status' => 'pending']);
        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_admin_controls_public_settings_and_dentist_visibility(): void
    {
        $dentist = $this->dentist();
        $dentist->update(['is_public' => false]);
        $administrator = $this->userWithRole(Role::ADMINISTRATOR);
        $this->actingAs($administrator)->put(route('configuracion-publica.update'), [
            'clinic_name' => 'Sonrisa Clara',
            'booking_weekdays' => [1, 3],
            'booking_starts_at' => '09:00',
            'booking_ends_at' => '17:00',
        ])->assertRedirect();
        $this->get(route('public.home'))->assertSeeText('Sonrisa Clara')->assertDontSeeText($dentist->user->name);

        $this->put(route('odontologos-publicos.update', $dentist), [
            'is_public' => 1,
            'specialty' => 'Odontología general',
        ])->assertRedirect();
        $this->get(route('public.home'))->assertSeeText($dentist->user->name)->assertSeeText('Odontología general');
    }

    /** @return array{Dentist, Treatment, CarbonImmutable} */
    private function bookableCatalog(): array
    {
        $dentist = $this->dentist();
        $treatment = Treatment::factory()->create(['is_public' => true, 'estimated_duration_minutes' => 60]);
        $date = CarbonImmutable::now(config('appointments.timezone'))->addDays(2)->startOfDay();
        PublicSiteSetting::query()->create([
            'booking_weekdays' => [$date->dayOfWeekIso],
            'booking_starts_at' => '09:00',
            'booking_ends_at' => '12:00',
        ]);

        return [$dentist, $treatment, $date];
    }

    /** @return array<string, int|string> */
    private function payload(Dentist $dentist, Treatment $treatment, CarbonImmutable $date): array
    {
        return [
            'treatment_id' => $treatment->id,
            'dentist_id' => $dentist->id,
            'date' => $date->toDateString(),
            'time' => '09:00',
            'name' => 'Ana López',
            'phone' => '55551234',
            'email' => 'ana@example.test',
        ];
    }

    private function dentist(): Dentist
    {
        $user = $this->userWithRole(Role::DENTIST);

        return Dentist::factory()->for($user, 'user')->create(['is_public' => true]);
    }

    private function userWithRole(string $role): User
    {
        return User::factory()->for(Role::query()->firstOrCreate(['name' => $role]))->create(['is_active' => true]);
    }
}
