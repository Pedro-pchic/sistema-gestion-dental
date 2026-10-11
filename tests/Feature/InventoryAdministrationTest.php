<?php

namespace Tests\Feature;

use App\Enums\InventoryMovementType;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class InventoryAdministrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_entry_increases_stock_and_creates_a_movement(): void
    {
        $user = $this->userWithRole(Role::ADMINISTRATOR);
        $inventory = $this->inventoryWithStock(8);

        $this->actingAs($user)
            ->post(route('inventario.movimientos.store'), [
                'product_id' => $inventory->product_id,
                'type' => InventoryMovementType::Entry->value,
                'quantity' => 5,
                'reason' => 'Recepción inicial',
            ])
            ->assertRedirectToRoute('inventario.movimientos.index', ['product_id' => $inventory->product_id]);

        $this->assertDatabaseHas('inventories', [
            'id' => $inventory->id,
            'stock' => 13,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_id' => $inventory->id,
            'user_id' => $user->id,
            'type' => InventoryMovementType::Entry->value,
            'quantity' => 5,
            'previous_stock' => 8,
            'resulting_stock' => 13,
            'reason' => 'Recepción inicial',
        ]);
    }

    public function test_exit_decreases_stock_and_creates_a_movement(): void
    {
        $user = $this->userWithRole(Role::ADMINISTRATOR);
        $inventory = $this->inventoryWithStock(8);

        $this->actingAs($user)
            ->post(route('inventario.movimientos.store'), [
                'product_id' => $inventory->product_id,
                'type' => InventoryMovementType::Exit->value,
                'quantity' => 3,
                'reason' => 'Producto dañado',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('inventories', [
            'id' => $inventory->id,
            'stock' => 5,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_id' => $inventory->id,
            'type' => InventoryMovementType::Exit->value,
            'quantity' => 3,
            'previous_stock' => 8,
            'resulting_stock' => 5,
        ]);
    }

    public function test_exit_greater_than_available_stock_is_rejected_without_changes(): void
    {
        $user = $this->userWithRole(Role::ADMINISTRATOR);
        $inventory = $this->inventoryWithStock(2);

        $this->actingAs($user)
            ->post(route('inventario.movimientos.store'), [
                'product_id' => $inventory->product_id,
                'type' => InventoryMovementType::Exit->value,
                'quantity' => 3,
            ])
            ->assertSessionHasErrors([
                'quantity' => 'La salida no puede superar las existencias disponibles.',
            ]);

        $this->assertDatabaseHas('inventories', [
            'id' => $inventory->id,
            'stock' => 2,
        ]);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_adjustment_records_previous_stock_resulting_stock_and_reason(): void
    {
        $user = $this->userWithRole(Role::ADMINISTRATOR);
        $inventory = $this->inventoryWithStock(10);

        $this->actingAs($user)
            ->post(route('inventario.movimientos.store'), [
                'product_id' => $inventory->product_id,
                'type' => InventoryMovementType::Adjustment->value,
                'quantity' => 4,
                'reason' => 'Conteo físico',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('inventories', [
            'id' => $inventory->id,
            'stock' => 4,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_id' => $inventory->id,
            'type' => InventoryMovementType::Adjustment->value,
            'quantity' => -6,
            'previous_stock' => 10,
            'resulting_stock' => 4,
            'reason' => 'Conteo físico',
        ]);
    }

    public function test_adjustment_requires_a_reason(): void
    {
        $user = $this->userWithRole(Role::ADMINISTRATOR);
        $inventory = $this->inventoryWithStock(10);

        $this->actingAs($user)
            ->post(route('inventario.movimientos.store'), [
                'product_id' => $inventory->product_id,
                'type' => InventoryMovementType::Adjustment->value,
                'quantity' => 4,
            ])
            ->assertSessionHasErrors([
                'reason' => 'El motivo es obligatorio para realizar un ajuste.',
            ]);

        $this->assertDatabaseHas('inventories', [
            'id' => $inventory->id,
            'stock' => 10,
        ]);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_stock_update_is_rolled_back_if_movement_creation_fails(): void
    {
        $inventory = $this->inventoryWithStock(10);
        $failureMessage = 'Movement could not be saved.';

        InventoryMovement::creating(static function (InventoryMovement $movement) use ($failureMessage): void {
            throw new RuntimeException($failureMessage);
        });

        try {
            $inventory->recordMovement(
                InventoryMovementType::Entry,
                2,
                null,
                null,
            );

            $this->fail('The movement creation failure should have been raised.');
        } catch (RuntimeException $exception) {
            $this->assertSame($failureMessage, $exception->getMessage());
        } finally {
            InventoryMovement::flushEventListeners();
        }

        $this->assertDatabaseHas('inventories', [
            'id' => $inventory->id,
            'stock' => 10,
        ]);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_movements_page_shows_the_product_and_audited_stock_values(): void
    {
        $user = $this->userWithRole(Role::ADMINISTRATOR);
        $inventory = $this->inventoryWithStock(0);
        $inventory->product()->update([
            'sku' => 'HIST-001',
            'name' => 'Zapato de historial',
        ]);
        $inventory->recordMovement(
            InventoryMovementType::Entry,
            2,
            'Recepción de prueba',
            $user,
        );

        $this->actingAs($user)
            ->get(route('inventario.movimientos.index', ['product_id' => $inventory->product_id]))
            ->assertOk()
            ->assertSeeText('HIST-001')
            ->assertSeeText('Zapato de historial')
            ->assertSeeText('Entrada')
            ->assertSeeText('+2')
            ->assertSeeText('0')
            ->assertSeeText('2')
            ->assertSeeText('Recepción de prueba')
            ->assertSeeText($user->name);
    }

    #[DataProvider('authorizedRoles')]
    public function test_administrator_and_warehouse_can_manage_inventory(string $roleName): void
    {
        $user = $this->userWithRole($roleName);
        $inventory = $this->inventoryWithStock(2);

        $this->actingAs($user)
            ->post(route('inventario.movimientos.store'), [
                'product_id' => $inventory->product_id,
                'type' => InventoryMovementType::Entry->value,
                'quantity' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('inventories', [
            'id' => $inventory->id,
            'stock' => 3,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_id' => $inventory->id,
            'user_id' => $user->id,
        ]);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function authorizedRoles(): array
    {
        return [
            'administrator' => [Role::ADMINISTRATOR],
            'warehouse' => [Role::WAREHOUSE],
        ];
    }

    public function test_user_without_an_authorized_role_cannot_manage_inventory(): void
    {
        $user = $this->userWithRole('Ventas');

        $this->actingAs($user)
            ->get(route('inventario.existencias'))
            ->assertForbidden();
    }

    public function test_stock_minimum_can_be_updated_and_low_stock_is_identified(): void
    {
        $user = $this->userWithRole(Role::ADMINISTRATOR);
        $inventory = $this->inventoryWithStock(5);

        $this->actingAs($user)
            ->patch(route('inventario.minimum-stock', $inventory), [
                'minimum_stock' => 5,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('inventories', [
            'id' => $inventory->id,
            'minimum_stock' => 5,
        ]);

        $this->actingAs($user)
            ->get(route('inventario.existencias'))
            ->assertOk()
            ->assertSeeText('Stock bajo')
            ->assertSeeText($inventory->product->sku);
    }

    public function test_negative_stock_minimum_is_rejected_without_changes(): void
    {
        $user = $this->userWithRole(Role::ADMINISTRATOR);
        $inventory = $this->inventoryWithStock(5, 2);

        $this->actingAs($user)
            ->patch(route('inventario.minimum-stock', $inventory), [
                'minimum_stock' => -1,
            ])
            ->assertSessionHasErrors([
                'minimum_stock' => 'El stock mínimo no puede ser negativo.',
            ]);

        $this->assertDatabaseHas('inventories', [
            'id' => $inventory->id,
            'minimum_stock' => 2,
        ]);
    }

    public function test_product_cannot_have_duplicate_inventory_records(): void
    {
        $product = Product::factory()->create();
        Inventory::factory()->for($product)->create();

        try {
            Inventory::factory()->for($product)->create();
            $this->fail('A second inventory record for the same product should be rejected.');
        } catch (QueryException) {
            $this->assertDatabaseCount('inventories', 1);
        }
    }

    private function inventoryWithStock(int $stock, int $minimumStock = 0): Inventory
    {
        return Inventory::factory()
            ->for(Product::factory())
            ->create([
                'stock' => $stock,
                'minimum_stock' => $minimumStock,
            ]);
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::factory()->create(['name' => $roleName]);

        return User::factory()->for($role)->create(['is_active' => true]);
    }
}
