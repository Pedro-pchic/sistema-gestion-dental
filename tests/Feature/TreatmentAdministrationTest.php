<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class TreatmentAdministrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_from_the_treatment_catalog(): void
    {
        $this->get(route('tratamientos.index'))
            ->assertRedirectToRoute('login');
    }

    public function test_administrator_can_create_edit_and_deactivate_treatments(): void
    {
        $administrator = $this->userWithRole(Role::ADMINISTRATOR);

        $this->actingAs($administrator)
            ->post(route('tratamientos.store'), [
                'name' => 'Limpieza dental',
                'description' => 'Profilaxis y pulido.',
                'price' => '425.50',
                'estimated_duration_minutes' => 45,
                'is_active' => true,
            ])
            ->assertRedirect();

        $treatment = Treatment::query()->where('name', 'Limpieza dental')->firstOrFail();
        $this->assertDatabaseHas('treatments', [
            'id' => $treatment->id,
            'price' => '425.50',
            'estimated_duration_minutes' => 45,
            'is_active' => true,
        ]);

        $this->actingAs($administrator)
            ->put(route('tratamientos.update', $treatment), [
                'name' => 'Profilaxis dental',
                'description' => 'Profilaxis y pulido actualizado.',
                'price' => '450.00',
                'estimated_duration_minutes' => 50,
                'is_active' => true,
            ])
            ->assertRedirectToRoute('tratamientos.show', $treatment);

        $this->actingAs($administrator)
            ->patch(route('tratamientos.status', $treatment))
            ->assertRedirect();

        $this->assertDatabaseHas('treatments', [
            'id' => $treatment->id,
            'name' => 'Profilaxis dental',
            'price' => '450.00',
            'is_active' => false,
        ]);
    }

    public function test_catalog_rejects_negative_prices_and_leaves_no_treatment(): void
    {
        $administrator = $this->userWithRole(Role::ADMINISTRATOR);

        $this->actingAs($administrator)
            ->post(route('tratamientos.store'), [
                'name' => 'Tratamiento con precio inválido',
                'price' => '-0.01',
                'estimated_duration_minutes' => 30,
            ])
            ->assertSessionHasErrors(['price' => 'El precio no puede ser negativo.']);

        $this->assertDatabaseCount('treatments', 0);
    }

    public function test_catalog_does_not_expose_physical_deletion(): void
    {
        $administrator = $this->userWithRole(Role::ADMINISTRATOR);
        $treatment = Treatment::factory()->create();

        $this->actingAs($administrator)
            ->delete(route('tratamientos.show', $treatment))
            ->assertMethodNotAllowed();

        $this->assertModelExists($treatment);
    }

    public function test_dentists_and_receptionists_can_consult_prices_but_cannot_manage_catalog(): void
    {
        $treatment = Treatment::factory()->create([
            'name' => 'Consulta general',
            'price' => '200.00',
        ]);

        foreach ([Role::DENTIST, Role::RECEPTIONIST] as $roleName) {
            $user = $this->userWithRole($roleName);

            $this->actingAs($user)
                ->get(route('tratamientos.index', ['search' => 'Consulta']))
                ->assertOk()
                ->assertSeeText($treatment->name)
                ->assertSeeText('200.00');

            $this->actingAs($user)
                ->get(route('tratamientos.create'))
                ->assertForbidden();
        }
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::factory()->create(['name' => $roleName]);

        return User::factory()->for($role)->create(['is_active' => true]);
    }
}
