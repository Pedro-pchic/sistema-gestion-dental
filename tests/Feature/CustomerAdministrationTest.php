<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CustomerAdministrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[DataProvider('commercialRoles')]
    public function test_authorized_user_can_create_edit_and_toggle_a_customer(string $roleName): void
    {
        $user = $this->userWithRole($roleName);

        $this->actingAs($user)
            ->post(route('clientes.store'), [
                'name' => 'Comercial Las Flores',
                'nit' => '1234567-8',
                'phone' => '5555-1212',
                'email' => 'ventas@lasflores.test',
            ])
            ->assertRedirect();

        $customer = Customer::query()->where('email', 'ventas@lasflores.test')->firstOrFail();

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Comercial Las Flores',
            'is_active' => true,
        ]);
        $this->assertDatabaseCount('users', 1);

        $this->actingAs($user)
            ->get(route('clientes.show', $customer))
            ->assertOk()
            ->assertSeeText('Comercial Las Flores')
            ->assertSeeText('ventas@lasflores.test');

        $this->actingAs($user)
            ->put(route('clientes.update', $customer), [
                'name' => 'Comercial Las Flores, S.A.',
                'nit' => '1234567-8',
                'phone' => '5555-1212',
                'email' => 'ventas@lasflores.test',
                'is_active' => false,
            ])
            ->assertRedirectToRoute('clientes.show', $customer);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Comercial Las Flores, S.A.',
            'is_active' => false,
        ]);

        $this->actingAs($user)
            ->patch(route('clientes.status', $customer))
            ->assertRedirect();

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'is_active' => true,
        ]);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function commercialRoles(): array
    {
        return [
            'administrator' => [Role::ADMINISTRATOR],
            'sales' => [Role::SALES],
        ];
    }

    public function test_customer_can_have_addresses_with_only_one_default(): void
    {
        $user = $this->userWithRole(Role::SALES);
        $customer = Customer::factory()->create();

        $this->actingAs($user)
            ->post(route('clientes.direcciones.store', $customer), [
                'label' => 'Casa',
                'address' => '5a avenida 10-20, zona 1',
                'city' => 'Guatemala',
                'department' => 'Guatemala',
                'is_default' => true,
            ])
            ->assertRedirectToRoute('clientes.show', $customer);

        $firstAddress = $customer->addresses()->firstOrFail();

        $this->actingAs($user)
            ->get(route('clientes.direcciones.create', $customer))
            ->assertOk()
            ->assertSeeText('Dirección completa');

        $this->actingAs($user)
            ->post(route('clientes.direcciones.store', $customer), [
                'label' => 'Trabajo',
                'address' => 'Avenida Reforma 1-10, zona 10',
                'city' => 'Guatemala',
                'department' => 'Guatemala',
                'is_default' => true,
            ])
            ->assertRedirectToRoute('clientes.show', $customer);

        $secondAddress = $customer->addresses()->where('label', 'Trabajo')->firstOrFail();

        $this->assertDatabaseHas('addresses', [
            'id' => $firstAddress->id,
            'is_default' => false,
        ]);
        $this->assertDatabaseHas('addresses', [
            'id' => $secondAddress->id,
            'is_default' => true,
        ]);
        $this->assertSame(1, $customer->addresses()->where('is_default', true)->count());

        $this->actingAs($user)
            ->get(route('clientes.direcciones.edit', [$customer, $firstAddress]))
            ->assertOk()
            ->assertSeeText('Dirección predeterminada');

        $this->actingAs($user)
            ->put(route('clientes.direcciones.update', [$customer, $firstAddress]), [
                'label' => 'Casa',
                'address' => 'Nueva dirección de casa',
                'city' => 'Mixco',
                'department' => 'Guatemala',
                'is_default' => true,
            ])
            ->assertRedirectToRoute('clientes.show', $customer);

        $this->assertDatabaseHas('addresses', [
            'id' => $firstAddress->id,
            'is_default' => true,
            'address' => 'Nueva dirección de casa',
        ]);
        $this->assertDatabaseHas('addresses', [
            'id' => $secondAddress->id,
            'is_default' => false,
        ]);
    }

    public function test_address_route_cannot_edit_an_address_of_another_customer(): void
    {
        $user = $this->userWithRole(Role::ADMINISTRATOR);
        $firstCustomer = Customer::factory()->create();
        $secondCustomer = Customer::factory()->create();
        $address = Address::factory()->for($firstCustomer)->create();

        $this->actingAs($user)
            ->get(route('clientes.direcciones.edit', [$secondCustomer, $address]))
            ->assertNotFound();
    }

    public function test_user_without_commercial_permission_cannot_manage_customers(): void
    {
        $user = $this->userWithRole(Role::PURCHASING);

        $this->actingAs($user)
            ->get(route('clientes.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('clientes.store'), ['name' => 'No autorizado'])
            ->assertForbidden();
    }

    public function test_customer_email_must_be_valid(): void
    {
        $user = $this->userWithRole(Role::ADMINISTRATOR);

        $this->actingAs($user)
            ->post(route('clientes.store'), [
                'name' => 'Cliente inválido',
                'email' => 'no-es-correo',
            ])
            ->assertSessionHasErrors([
                'email' => 'Ingresa un correo electrónico válido.',
            ]);

        $this->assertDatabaseCount('customers', 0);
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::factory()->create(['name' => $roleName]);

        return User::factory()->for($role)->create(['is_active' => true]);
    }
}
