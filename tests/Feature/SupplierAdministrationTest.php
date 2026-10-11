<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SupplierAdministrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[DataProvider('purchaseManagementRoles')]
    public function test_authorized_user_can_create_update_and_toggle_a_supplier(string $roleName): void
    {
        $user = $this->userWithRole($roleName);

        $this->actingAs($user)
            ->post(route('proveedores.store'), [
                'name' => 'Calzado del Norte',
                'nit' => '1234567-8',
                'contact_name' => 'Ana López',
                'phone' => '5555-0101',
                'email' => 'contacto@calzadonorte.test',
                'address' => 'Zona 1, Guatemala',
            ])
            ->assertRedirect();

        $supplier = Supplier::query()->where('email', 'contacto@calzadonorte.test')->firstOrFail();

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => 'Calzado del Norte',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('proveedores.show', $supplier))
            ->assertOk()
            ->assertSeeText('Calzado del Norte')
            ->assertSeeText('Ana López');

        $this->actingAs($user)
            ->put(route('proveedores.update', $supplier), [
                'name' => 'Calzado del Norte, S.A.',
                'nit' => '1234567-8',
                'contact_name' => 'Ana López',
                'phone' => '5555-0101',
                'email' => 'contacto@calzadonorte.test',
                'address' => 'Zona 4, Guatemala',
                'is_active' => false,
            ])
            ->assertRedirectToRoute('proveedores.show', $supplier);

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => 'Calzado del Norte, S.A.',
            'is_active' => false,
        ]);

        $this->actingAs($user)
            ->patch(route('proveedores.status', $supplier))
            ->assertRedirect();

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'is_active' => true,
        ]);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function purchaseManagementRoles(): array
    {
        return [
            'administrator' => [Role::ADMINISTRATOR],
            'purchasing' => [Role::PURCHASING],
        ];
    }

    public function test_user_without_purchase_management_permission_cannot_access_suppliers(): void
    {
        $user = $this->userWithRole('Ventas');

        $this->actingAs($user)
            ->get(route('proveedores.index'))
            ->assertForbidden();
    }

    public function test_supplier_email_must_be_valid(): void
    {
        $user = $this->userWithRole(Role::ADMINISTRATOR);

        $this->actingAs($user)
            ->post(route('proveedores.store'), [
                'name' => 'Proveedor inválido',
                'email' => 'no-es-correo',
            ])
            ->assertSessionHasErrors([
                'email' => 'Ingresa un correo electrónico válido.',
            ]);

        $this->assertDatabaseCount('suppliers', 0);
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::factory()->create(['name' => $roleName]);

        return User::factory()->for($role)->create(['is_active' => true]);
    }
}
