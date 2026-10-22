<?php

namespace Tests\Feature;

use App\Models\PublicSiteSetting;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Inertia;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InertiaPilotTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_pilot_pages_keep_authentication_authorization_and_blade_navigation(): void
    {
        $this->get(route('dashboard'))->assertRedirectToRoute('login');
        $this->get(route('configuracion-publica.edit'))->assertRedirectToRoute('login');

        $dentist = $this->userWithRole(Role::DENTIST);
        $this->actingAs($dentist)->get(route('configuracion-publica.edit'))->assertForbidden();

        $administrator = $this->userWithRole(Role::ADMINISTRATOR);
        $response = $this->actingAs($administrator)->get(route('configuracion-publica.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('PublicSite/Edit'));

        $this->assertSame(route('configuracion-publica.update'), $response->inertiaProps('updateUrl'));
        $this->assertSame(route('dashboard'), $response->inertiaProps('routes.dashboard'));
        $this->assertSame(route('logout'), $response->inertiaProps('routes.logout'));
        $this->assertNotEmpty($response->inertiaProps('csrfToken'));
        $this->assertContains(route('pacientes.index'), array_column($response->inertiaProps('navigation'), 'href'));
        $this->actingAs($administrator)->get(route('pacientes.index'))->assertOk()->assertViewIs('patients.index');
        $this->actingAs($administrator)->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Dashboard/Index'));
    }

    public function test_inertia_visit_exposes_only_public_settings_fields(): void
    {
        PublicSiteSetting::query()->create(['clinic_name' => 'Clínica de prueba', 'hero_image_path' => 'private-example.jpg']);
        $administrator = $this->userWithRole(Role::ADMINISTRATOR);
        $this->actingAs($administrator)->get(route('configuracion-publica.edit'))->assertOk();

        $response = $this->actingAs($administrator)->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => Inertia::getVersion()])
            ->get(route('configuracion-publica.edit'))
            ->assertOk()
            ->assertHeader('X-Inertia', 'true')
            ->assertJsonPath('component', 'PublicSite/Edit');

        $this->assertSame('Clínica de prueba', $response->json('props.settings.clinic_name'));
        $this->assertArrayNotHasKey('hero_image_path', $response->json('props.settings'));
    }

    public function test_inertia_form_validation_and_flash_follow_existing_laravel_update(): void
    {
        $administrator = $this->userWithRole(Role::ADMINISTRATOR);
        $this->actingAs($administrator)->from(route('configuracion-publica.edit'))
            ->post(route('configuracion-publica.update'), [
                '_method' => 'put',
                'booking_hours' => [1 => ['start' => '18:00', 'end' => '08:00']],
            ], ['X-Inertia' => 'true'])
            ->assertRedirect(route('configuracion-publica.edit'))
            ->assertSessionHasErrors('booking_hours.1.end');

        $this->actingAs($administrator)->from(route('configuracion-publica.edit'))
            ->post(route('configuracion-publica.update'), [
                '_method' => 'put',
                'clinic_name' => 'Sonrisa Clara',
                'booking_hours' => [1 => ['start' => '08:00', 'end' => '17:00']],
            ], ['X-Inertia' => 'true'])
            ->assertRedirect(route('configuracion-publica.edit'))
            ->assertSessionHas('status', 'Configuración pública actualizada.');

        $this->assertDatabaseHas('public_site_settings', ['id' => 1, 'clinic_name' => 'Sonrisa Clara']);
        $this->actingAs($administrator)->get(route('configuracion-publica.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('PublicSite/Edit'));
    }

    private function userWithRole(string $roleName): User
    {
        return User::factory()->for(Role::query()->firstOrCreate(['name' => $roleName]))->create(['is_active' => true]);
    }
}
