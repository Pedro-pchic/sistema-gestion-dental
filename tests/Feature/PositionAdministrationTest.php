<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PositionAdministrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_human_resources_user_can_create_position_for_department(): void
    {
        $department = Department::factory()->create(['name' => 'Finanzas']);

        $this->actingAs($this->humanResourcesUser())
            ->post(route('puestos.store'), [
                'department_id' => $department->id,
                'name' => 'Analista',
                'description' => 'Análisis financiero.',
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('positions', [
            'department_id' => $department->id,
            'name' => 'Analista',
            'is_active' => true,
        ]);
    }

    public function test_position_pages_render_and_position_can_be_updated(): void
    {
        $user = $this->humanResourcesUser();
        $department = Department::factory()->create(['name' => 'Ventas']);
        $position = Position::factory()->for($department)->create(['name' => 'Asesor']);

        $this->actingAs($user)
            ->get(route('puestos.index'))
            ->assertOk()
            ->assertSeeText('Asesor')
            ->assertSeeText('Ventas');

        $this->get(route('puestos.create'))->assertOk();
        $this->get(route('puestos.show', $position))->assertOk();
        $this->get(route('puestos.edit', $position))->assertOk();

        $this->put(route('puestos.update', $position), [
            'department_id' => $department->id,
            'name' => 'Asesor de ventas',
            'description' => 'Atención comercial.',
            'is_active' => true,
        ])->assertRedirectToRoute('puestos.show', $position);

        $this->assertDatabaseHas('positions', [
            'id' => $position->id,
            'name' => 'Asesor de ventas',
        ]);
    }

    public function test_position_rejects_a_nonexistent_department(): void
    {
        $this->actingAs($this->humanResourcesUser())
            ->post(route('puestos.store'), [
                'department_id' => PHP_INT_MAX,
                'name' => 'Analista',
            ])
            ->assertSessionHasErrors('department_id');

        $this->assertDatabaseMissing('positions', ['name' => 'Analista']);
    }

    public function test_position_name_is_unique_within_its_department(): void
    {
        $department = Department::factory()->create();
        Position::factory()->for($department)->create(['name' => 'Analista']);
        $otherDepartment = Department::factory()->create();
        $humanResourcesUser = $this->humanResourcesUser();

        $this->actingAs($humanResourcesUser)
            ->post(route('puestos.store'), [
                'department_id' => $department->id,
                'name' => 'Analista',
            ])
            ->assertSessionHasErrors('name');

        $this->actingAs($humanResourcesUser)
            ->post(route('puestos.store'), [
                'department_id' => $otherDepartment->id,
                'name' => 'Analista',
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('positions', 2);
    }

    public function test_deactivating_position_preserves_employee_assignment(): void
    {
        $position = Position::factory()->create(['is_active' => true]);
        $employee = Employee::factory()->for($position)->create();

        $this->actingAs($this->humanResourcesUser())
            ->patch(route('puestos.status', $position))
            ->assertRedirect();

        $this->assertDatabaseHas('positions', ['id' => $position->id, 'is_active' => false]);
        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'position_id' => $position->id]);
    }

    private function humanResourcesUser(): User
    {
        $role = Role::factory()->create(['name' => Role::HUMAN_RESOURCES]);

        return User::factory()->for($role)->create(['is_active' => true]);
    }
}
