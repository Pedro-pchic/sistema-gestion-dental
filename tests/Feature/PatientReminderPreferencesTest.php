<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PatientReminderPreferencesTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_receptionist_can_record_and_revoke_email_reminder_consent(): void
    {
        $this->freezeTime();
        $receptionist = $this->userWithRole(Role::RECEPTIONIST);
        $this->actingAs($receptionist)
            ->post(route('pacientes.store'), [
                'first_name' => 'Ana',
                'last_name' => 'Consentimiento',
                'email' => 'ana@example.test',
                'email_reminders_enabled' => '1',
            ])
            ->assertRedirect();

        $patient = Patient::query()->where('email', 'ana@example.test')->firstOrFail();
        $this->assertTrue($patient->email_reminders_enabled);
        $this->assertNotNull($patient->email_reminders_consent_at);
        $this->assertNull($patient->email_reminders_revoked_at);
        $this->assertSame($receptionist->getKey(), (int) $patient->notification_preferences_updated_by);

        $this->actingAs($receptionist)
            ->put(route('pacientes.update', $patient), [
                'first_name' => 'Ana',
                'last_name' => 'Consentimiento',
                'phone' => '+502 5555-1234',
                'email' => 'ana@example.test',
                'email_reminders_enabled' => '0',
                'is_active' => '1',
            ])
            ->assertRedirectToRoute('pacientes.show', $patient);

        $patient->refresh();
        $this->assertFalse($patient->email_reminders_enabled);
        $this->assertNotNull($patient->email_reminders_revoked_at);
        $this->assertSame($receptionist->getKey(), (int) $patient->notification_preferences_updated_by);
    }

    public function test_requires_a_valid_email_before_recording_email_reminder_consent(): void
    {
        $receptionist = $this->userWithRole(Role::RECEPTIONIST);

        $this->actingAs($receptionist)
            ->post(route('pacientes.store'), [
                'first_name' => 'Ana',
                'last_name' => 'Sin correo',
                'email_reminders_enabled' => '1',
            ])
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('patients', 0);
    }

    public function test_rejects_malformed_contact_phone_numbers(): void
    {
        $receptionist = $this->userWithRole(Role::RECEPTIONIST);

        $this->actingAs($receptionist)
            ->post(route('pacientes.store'), [
                'first_name' => 'Ana',
                'last_name' => 'Teléfono inválido',
                'phone' => '555ABC',
            ])
            ->assertSessionHasErrors('phone');

        $this->assertDatabaseCount('patients', 0);
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::query()->firstOrCreate(['name' => $roleName]);

        return User::factory()->for($role)->create(['is_active' => true]);
    }
}
