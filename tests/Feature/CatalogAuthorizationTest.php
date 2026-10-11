<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CatalogAuthorizationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_warehouse_user_can_access_categories(): void
    {
        $warehouseUser = $this->userWithRole(Role::WAREHOUSE);

        $this->actingAs($warehouseUser)
            ->get(route('categorias.index'))
            ->assertOk();
    }

    public function test_warehouse_user_can_access_products(): void
    {
        $warehouseUser = $this->userWithRole(Role::WAREHOUSE);

        $this->actingAs($warehouseUser)
            ->get(route('productos.index'))
            ->assertOk();
    }

    public function test_user_without_authorized_role_cannot_access_categories(): void
    {
        $user = $this->userWithRole('Ventas');

        $this->actingAs($user)
            ->get(route('categorias.index'))
            ->assertForbidden();
    }

    public function test_user_without_authorized_role_cannot_access_products(): void
    {
        $user = $this->userWithRole('Ventas');

        $this->actingAs($user)
            ->get(route('productos.index'))
            ->assertForbidden();
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::factory()->create(['name' => $roleName]);

        return User::factory()->for($role)->create(['is_active' => true]);
    }
}
