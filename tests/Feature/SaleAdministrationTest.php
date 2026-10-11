<?php

namespace Tests\Feature;

use App\Actions\ConfirmOrderAsSale;
use App\Actions\SaveOrder;
use App\Enums\InventoryMovementType;
use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class SaleAdministrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_confirmed_order_creates_one_sale_and_exit_movements_through_inventory(): void
    {
        $user = $this->userWithRole(Role::SALES);
        $customer = Customer::factory()->create();
        $firstInventory = $this->inventoryWithStock(7);
        $secondInventory = $this->inventoryWithStock(5);
        $order = $this->createConfirmedOrder($customer, [
            ['product_id' => $firstInventory->product_id, 'quantity' => 2, 'unit_price' => '18.50'],
            ['product_id' => $secondInventory->product_id, 'quantity' => 3, 'unit_price' => '4.20'],
        ]);

        $this->actingAs($user)
            ->post(route('pedidos.sale.store', $order))
            ->assertRedirect();

        $sale = Sale::query()->firstOrFail();
        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'status' => 'confirmed',
            'total' => '49.60',
        ]);
        $this->assertDatabaseHas('sale_details', [
            'sale_id' => $sale->id,
            'product_id' => $firstInventory->product_id,
            'quantity' => 2,
            'unit_price' => '18.50',
            'subtotal' => '37.00',
        ]);
        $this->assertDatabaseHas('sale_details', [
            'sale_id' => $sale->id,
            'product_id' => $secondInventory->product_id,
            'quantity' => 3,
            'unit_price' => '4.20',
            'subtotal' => '12.60',
        ]);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => OrderStatus::Completed->value]);
        $this->assertDatabaseHas('inventories', ['id' => $firstInventory->id, 'stock' => 5]);
        $this->assertDatabaseHas('inventories', ['id' => $secondInventory->id, 'stock' => 2]);

        $firstMovement = InventoryMovement::query()->where('inventory_id', $firstInventory->id)->firstOrFail();
        $secondMovement = InventoryMovement::query()->where('inventory_id', $secondInventory->id)->firstOrFail();

        $this->assertSame(InventoryMovementType::Exit, $firstMovement->type);
        $this->assertSame(2, $firstMovement->quantity);
        $this->assertSame(7, $firstMovement->previous_stock);
        $this->assertSame(5, $firstMovement->resulting_stock);
        $this->assertSame($user->id, $firstMovement->user_id);
        $this->assertSame(InventoryMovementType::Exit, $secondMovement->type);
        $this->assertSame(3, $secondMovement->quantity);

        $this->actingAs($user)
            ->get(route('ventas.index'))
            ->assertOk()
            ->assertSeeText($sale->number)
            ->assertSeeText($order->number);

        $this->actingAs($user)
            ->get(route('ventas.show', $sale))
            ->assertOk()
            ->assertSeeText('49.60');
    }

    public function test_insufficient_stock_rejects_the_sale_before_any_inventory_or_sales_write(): void
    {
        $user = $this->userWithRole(Role::SALES);
        $customer = Customer::factory()->create();
        $firstInventory = $this->inventoryWithStock(6);
        $secondInventory = $this->inventoryWithStock(2);
        $order = $this->createConfirmedOrder($customer, [
            ['product_id' => $firstInventory->product_id, 'quantity' => 4, 'unit_price' => '10.00'],
            ['product_id' => $secondInventory->product_id, 'quantity' => 3, 'unit_price' => '7.00'],
        ]);

        $this->actingAs($user)
            ->post(route('pedidos.sale.store', $order))
            ->assertSessionHasErrors('order');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => OrderStatus::Confirmed->value]);
        $this->assertDatabaseHas('inventories', ['id' => $firstInventory->id, 'stock' => 6]);
        $this->assertDatabaseHas('inventories', ['id' => $secondInventory->id, 'stock' => 2]);
        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('sale_details', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_a_movement_error_rolls_back_the_sale_all_movements_and_order_completion(): void
    {
        $user = $this->userWithRole(Role::SALES);
        $customer = Customer::factory()->create();
        $firstInventory = $this->inventoryWithStock(8);
        $secondInventory = $this->inventoryWithStock(9);
        $order = $this->createConfirmedOrder($customer, [
            ['product_id' => $firstInventory->product_id, 'quantity' => 2, 'unit_price' => '3.00'],
            ['product_id' => $secondInventory->product_id, 'quantity' => 4, 'unit_price' => '5.00'],
        ]);
        $movementAttempts = 0;
        $failureMessage = 'Movement could not be saved.';

        InventoryMovement::creating(static function (InventoryMovement $movement) use (&$movementAttempts, $failureMessage): void {
            $movementAttempts++;

            if ($movementAttempts === 2) {
                throw new RuntimeException($failureMessage);
            }
        });

        try {
            app(ConfirmOrderAsSale::class)->handle($order, $user);
            $this->fail('The movement error should have been raised.');
        } catch (RuntimeException $exception) {
            $this->assertSame($failureMessage, $exception->getMessage());
        } finally {
            InventoryMovement::flushEventListeners();
        }

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => OrderStatus::Confirmed->value]);
        $this->assertDatabaseHas('inventories', ['id' => $firstInventory->id, 'stock' => 8]);
        $this->assertDatabaseHas('inventories', ['id' => $secondInventory->id, 'stock' => 9]);
        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('sale_details', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_completed_order_cannot_generate_a_second_sale(): void
    {
        $user = $this->userWithRole(Role::SALES);
        $customer = Customer::factory()->create();
        $inventory = $this->inventoryWithStock(5);
        $order = $this->createConfirmedOrder($customer, [
            ['product_id' => $inventory->product_id, 'quantity' => 2, 'unit_price' => '6.00'],
        ]);

        $this->actingAs($user)
            ->post(route('pedidos.sale.store', $order))
            ->assertRedirect();

        $this->actingAs($user)
            ->post(route('pedidos.sale.store', $order))
            ->assertSessionHasErrors('order');

        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseCount('inventory_movements', 1);
        $this->assertDatabaseHas('inventories', ['id' => $inventory->id, 'stock' => 3]);
    }

    public function test_user_without_commercial_permission_cannot_register_a_sale(): void
    {
        $user = $this->userWithRole(Role::PURCHASING);
        $customer = Customer::factory()->create();
        $inventory = $this->inventoryWithStock(4);
        $order = $this->createConfirmedOrder($customer, [
            ['product_id' => $inventory->product_id, 'quantity' => 1, 'unit_price' => '2.00'],
        ]);

        $this->actingAs($user)
            ->post(route('pedidos.sale.store', $order))
            ->assertForbidden();

        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int, unit_price: string}>  $details
     */
    private function createConfirmedOrder(Customer $customer, array $details): Order
    {
        $order = app(SaveOrder::class)->handle([
            'customer_id' => $customer->id,
            'order_date' => '2026-09-28',
            'details' => $details,
        ]);
        $order->transitionTo(OrderStatus::Confirmed);

        return $order;
    }

    private function inventoryWithStock(int $stock): Inventory
    {
        return Inventory::factory()
            ->for(Product::factory())
            ->create(['stock' => $stock]);
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::factory()->create(['name' => $roleName]);

        return User::factory()->for($role)->create(['is_active' => true]);
    }
}
