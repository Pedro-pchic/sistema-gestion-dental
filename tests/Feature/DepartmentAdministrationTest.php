<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DepartmentAdministrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_human_resources_user_can_create_department(): void
    {
        $this->actingAs($this->humanResourcesUser())
            ->post(route('departamentos.store'), [
                'name' => 'Contabilidad',
                'description' => 'Gestión contable.',
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('departments', [
            'name' => 'Contabilidad',
            'description' => 'Gestión contable.',
            'is_active' => true,
        ]);
    }

    public function test_administrator_can_manage_departments_and_positions(): void
    {
        $role = Role::factory()->create(['name' => Role::ADMINISTRATOR]);
        $administrator = User::factory()->for($role)->create(['is_active' => true]);

        $this->actingAs($administrator)
            ->get(route('departamentos.index'))
            ->assertOk();

        $this->get(route('puestos.index'))->assertOk();
    }

    public function test_department_pages_render_and_department_can_be_updated(): void
    {
        $user = $this->humanResourcesUser();
        $department = Department::factory()->create(['name' => 'Bodega']);

        $this->actingAs($user)
            ->get(route('departamentos.index'))
            ->assertOk()
            ->assertSeeText('Bodega');

        $this->get(route('departamentos.show', $department))->assertOk();
        $this->get(route('departamentos.edit', $department))->assertOk();

        $this->put(route('departamentos.update', $department), [
            'name' => 'Almacén',
            'description' => 'Inventario y resguardo.',
            'is_active' => true,
        ])->assertRedirectToRoute('departamentos.show', $department);

        $this->assertDatabaseHas('departments', [
            'id' => $department->id,
            'name' => 'Almacén',
        ]);
    }

    public function test_user_without_authorized_role_cannot_manage_departments(): void
    {
        $role = Role::factory()->create(['name' => Role::SALES]);
        $user = User::factory()->for($role)->create(['is_active' => true]);

        $this->actingAs($user)
            ->get(route('departamentos.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('departamentos.store'), ['name' => 'Ventas'])
            ->assertForbidden();

        $this->assertDatabaseMissing('departments', ['name' => 'Ventas']);
    }

    public function test_duplicate_department_name_is_rejected(): void
    {
        $department = Department::factory()->create(['name' => 'Operaciones']);

        $this->actingAs($this->humanResourcesUser())
            ->post(route('departamentos.store'), ['name' => $department->name])
            ->assertSessionHasErrors('name');
    }

    public function test_deactivating_department_preserves_its_positions(): void
    {
        $department = Department::factory()->create(['is_active' => true]);
        $position = Position::factory()->for($department)->create();

        $this->actingAs($this->humanResourcesUser())
            ->patch(route('departamentos.status', $department))
            ->assertRedirect();

        $this->assertDatabaseHas('departments', ['id' => $department->id, 'is_active' => false]);
        $this->assertDatabaseHas('positions', ['id' => $position->id, 'department_id' => $department->id]);
    }

    private function humanResourcesUser(): User
    {
        $role = Role::factory()->create(['name' => Role::HUMAN_RESOURCES]);

        return User::factory()->for($role)->create(['is_active' => true]);
    }
}
