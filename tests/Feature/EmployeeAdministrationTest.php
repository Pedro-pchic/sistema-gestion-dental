<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class EmployeeAdministrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_human_resources_user_can_create_employee(): void
    {
        $humanResourcesUser = $this->humanResourcesUser();
        $associatedUser = User::factory()->create();

        $this->actingAs($humanResourcesUser)
            ->post(route('empleados.store'), [
                'user_id' => $associatedUser->id,
                'nombres' => 'Ana María',
                'apellidos' => 'López Pérez',
                'telefono' => '5555-1234',
                'direccion' => 'Ciudad de Guatemala',
                'puesto' => 'Analista',
                'fecha_contratacion' => '2026-09-28',
                'is_active' => true,
            ])
            ->assertRedirectToRoute('empleados.index');

        $this->assertDatabaseHas('employees', [
            'user_id' => $associatedUser->id,
            'nombres' => 'Ana María',
            'apellidos' => 'López Pérez',
            'is_active' => true,
        ]);
    }

    public function test_human_resources_user_can_view_and_edit_employee(): void
    {
        $humanResourcesUser = $this->humanResourcesUser();
        $employee = Employee::factory()->create([
            'nombres' => 'Nombre Inicial',
            'apellidos' => 'Apellido Inicial',
        ]);

        $this->actingAs($humanResourcesUser)
            ->get(route('empleados.show', $employee))
            ->assertOk()
            ->assertSeeText('Nombre Inicial Apellido Inicial');

        $this->actingAs($humanResourcesUser)
            ->put(route('empleados.update', $employee), [
                'user_id' => null,
                'nombres' => 'Nombre Actualizado',
                'apellidos' => 'Apellido Actualizado',
                'telefono' => null,
                'direccion' => null,
                'puesto' => 'Supervisora',
                'fecha_contratacion' => null,
                'is_active' => true,
            ])
            ->assertRedirectToRoute('empleados.show', $employee);

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'nombres' => 'Nombre Actualizado',
            'apellidos' => 'Apellido Actualizado',
            'puesto' => 'Supervisora',
        ]);
    }

    public function test_human_resources_user_can_toggle_employee_status(): void
    {
        $humanResourcesUser = $this->humanResourcesUser();
        $employee = Employee::factory()->create(['is_active' => true]);

        $this->actingAs($humanResourcesUser)
            ->patch(route('empleados.status', $employee))
            ->assertRedirect();

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'is_active' => false,
        ]);
    }

    public function test_administrator_can_manage_employees(): void
    {
        $role = Role::factory()->create(['name' => Role::ADMINISTRATOR]);
        $administrator = User::factory()->for($role)->create(['is_active' => true]);

        $this->actingAs($administrator)
            ->get(route('empleados.index'))
            ->assertOk();
    }

    public function test_user_without_authorized_role_cannot_manage_employees(): void
    {
        $role = Role::factory()->create(['name' => 'Ventas']);
        $user = User::factory()->for($role)->create(['is_active' => true]);

        $this->actingAs($user)
            ->get(route('empleados.index'))
            ->assertForbidden();
    }

    private function humanResourcesUser(): User
    {
        $role = Role::factory()->create(['name' => Role::HUMAN_RESOURCES]);

        return User::factory()->for($role)->create(['is_active' => true]);
    }
}
