<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class EmployeePositionAssignmentTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_human_resources_user_can_assign_employee_to_an_active_position(): void
    {
        $department = Department::factory()->create(['name' => 'Compras']);
        $position = Position::factory()->for($department)->create(['name' => 'Comprador']);
        $humanResourcesUser = $this->humanResourcesUser();

        $this->actingAs($humanResourcesUser)
            ->post(route('empleados.store'), [
                'nombres' => 'Ana María',
                'apellidos' => 'López Pérez',
                'puesto' => 'Compradora senior',
                'position_id' => $position->id,
                'is_active' => true,
            ])
            ->assertRedirect();

        $employee = Employee::query()->where('nombres', 'Ana María')->firstOrFail();

        $this->assertSame($position->id, $employee->position->id);
        $this->assertSame($department->id, $employee->position->department->id);

        $this->actingAs($humanResourcesUser)
            ->get(route('empleados.show', $employee))
            ->assertOk()
            ->assertSeeText('Comprador')
            ->assertSeeText('Compras');
    }

    public function test_employee_cannot_be_assigned_to_an_inactive_position(): void
    {
        $position = Position::factory()->create(['is_active' => false]);

        $this->actingAs($this->humanResourcesUser())
            ->post(route('empleados.store'), [
                'nombres' => 'Luis',
                'apellidos' => 'García',
                'position_id' => $position->id,
            ])
            ->assertSessionHasErrors('position_id');

        $this->assertDatabaseMissing('employees', ['nombres' => 'Luis']);
    }

    public function test_employee_without_a_position_continues_to_display(): void
    {
        $employee = Employee::factory()->create(['position_id' => null]);

        $this->actingAs($this->humanResourcesUser())
            ->get(route('empleados.index'))
            ->assertOk()
            ->assertSeeText('Sin puesto asignado');

        $this->get(route('empleados.show', $employee))
            ->assertOk()
            ->assertSeeText('Sin puesto asignado');
    }

    private function humanResourcesUser(): User
    {
        $role = Role::factory()->create(['name' => Role::HUMAN_RESOURCES]);

        return User::factory()->for($role)->create(['is_active' => true]);
    }
}
