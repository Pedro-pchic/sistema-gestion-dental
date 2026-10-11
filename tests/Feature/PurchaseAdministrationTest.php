<?php

namespace Tests\Feature;

use App\Actions\ReceivePurchase;
use App\Actions\SavePurchaseDraft;
use App\Enums\InventoryMovementType;
use App\Enums\PurchaseStatus;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class PurchaseAdministrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_purchase_draft_calculates_total_from_multiple_details(): void
    {
        $user = $this->userWithRole(Role::PURCHASING);
        $supplier = Supplier::factory()->create();
        $firstProduct = Product::factory()->create();
        $secondProduct = Product::factory()->create();

        $this->actingAs($user)
            ->post(route('compras.store'), [
                'supplier_id' => $supplier->id,
                'order_date' => '2026-09-28',
                'notes' => 'Reposición mensual',
                'total' => '0.01',
                'details' => [
                    ['product_id' => $firstProduct->id, 'quantity' => 2, 'unit_cost' => '12.50'],
                    ['product_id' => $secondProduct->id, 'quantity' => 3, 'unit_cost' => '5.00'],
                ],
            ])
            ->assertRedirect();

        $purchase = Purchase::query()->with('details')->firstOrFail();

        $this->assertSame(PurchaseStatus::Draft, $purchase->status);
        $this->assertSame('40.00', $purchase->total);
        $this->assertSame(2, $purchase->details->count());
        $this->assertDatabaseHas('purchase_details', [
            'purchase_id' => $purchase->id,
            'product_id' => $firstProduct->id,
            'quantity' => 2,
            'unit_cost' => '12.50',
            'subtotal' => '25.00',
        ]);
        $this->assertDatabaseHas('purchase_details', [
            'purchase_id' => $purchase->id,
            'product_id' => $secondProduct->id,
            'quantity' => 3,
            'unit_cost' => '5.00',
            'subtotal' => '15.00',
        ]);
        $this->assertStringStartsWith('OC-', $purchase->number);
    }

    public function test_purchase_requires_an_active_supplier_and_at_least_one_detail(): void
    {
        $user = $this->userWithRole(Role::ADMINISTRATOR);
        $supplier = Supplier::factory()->create(['is_active' => false]);
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->post(route('compras.store'), [
                'supplier_id' => $supplier->id,
                'order_date' => '2026-09-28',
                'details' => [
                    ['product_id' => $product->id, 'quantity' => 1, 'unit_cost' => '3.00'],
                ],
            ])
            ->assertSessionHasErrors('supplier_id');

        $this->assertDatabaseCount('purchases', 0);

        $activeSupplier = Supplier::factory()->create();

        $this->actingAs($user)
            ->post(route('compras.store'), [
                'supplier_id' => $activeSupplier->id,
                'order_date' => '2026-09-28',
                'details' => [],
            ])
            ->assertSessionHasErrors('details');

        $this->assertDatabaseCount('purchases', 0);
    }

    public function test_purchase_detail_quantity_and_unit_cost_are_validated(): void
    {
        $user = $this->userWithRole(Role::PURCHASING);
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->post(route('compras.store'), [
                'supplier_id' => $supplier->id,
                'order_date' => '2026-09-28',
                'details' => [
                    ['product_id' => $product->id, 'quantity' => 0, 'unit_cost' => '-1'],
                ],
            ])
            ->assertSessionHasErrors(['details.0.quantity', 'details.0.unit_cost']);

        $this->assertDatabaseCount('purchases', 0);
    }

    public function test_purchase_can_be_submitted_and_received_by_warehouse(): void
    {
        $purchaser = $this->userWithRole(Role::PURCHASING);
        $warehouseUser = $this->userWithRole(Role::WAREHOUSE);
        $firstInventory = $this->inventoryWithStock(4);
        $secondInventory = $this->inventoryWithStock(10);
        $purchase = $this->createDraft([
            ['product_id' => $firstInventory->product_id, 'quantity' => 5, 'unit_cost' => '2.00'],
            ['product_id' => $secondInventory->product_id, 'quantity' => 3, 'unit_cost' => '7.50'],
        ]);

        $this->actingAs($purchaser)
            ->post(route('compras.submit', $purchase))
            ->assertRedirectToRoute('compras.show', $purchase);

        $this->assertDatabaseHas('purchases', [
            'id' => $purchase->id,
            'status' => PurchaseStatus::Pending->value,
        ]);

        $this->actingAs($warehouseUser)
            ->post(route('compras.receive', $purchase))
            ->assertRedirectToRoute('compras.show', $purchase);

        $this->assertDatabaseHas('purchases', [
            'id' => $purchase->id,
            'status' => PurchaseStatus::Received->value,
        ]);
        $this->assertDatabaseHas('inventories', [
            'id' => $firstInventory->id,
            'stock' => 9,
        ]);
        $this->assertDatabaseHas('inventories', [
            'id' => $secondInventory->id,
            'stock' => 13,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_id' => $firstInventory->id,
            'user_id' => $warehouseUser->id,
            'type' => InventoryMovementType::Entry->value,
            'quantity' => 5,
            'previous_stock' => 4,
            'resulting_stock' => 9,
            'reason' => "Recepción de compra {$purchase->number}",
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_id' => $secondInventory->id,
            'user_id' => $warehouseUser->id,
            'type' => InventoryMovementType::Entry->value,
            'quantity' => 3,
            'previous_stock' => 10,
            'resulting_stock' => 13,
            'reason' => "Recepción de compra {$purchase->number}",
        ]);
    }

    public function test_purchase_receipt_is_atomic_when_a_movement_cannot_be_created(): void
    {
        $receiver = $this->userWithRole(Role::WAREHOUSE);
        $firstInventory = $this->inventoryWithStock(4);
        $secondInventory = $this->inventoryWithStock(10);
        $purchase = $this->createDraft([
            ['product_id' => $firstInventory->product_id, 'quantity' => 5, 'unit_cost' => '2.00'],
            ['product_id' => $secondInventory->product_id, 'quantity' => 3, 'unit_cost' => '7.50'],
        ]);
        $purchase->transitionTo(PurchaseStatus::Pending);
        $movementAttempts = 0;
        $failureMessage = 'Movement could not be saved.';

        InventoryMovement::creating(static function (InventoryMovement $movement) use (&$movementAttempts, $failureMessage): void {
            $movementAttempts++;

            if ($movementAttempts === 2) {
                throw new RuntimeException($failureMessage);
            }
        });

        try {
            app(ReceivePurchase::class)->handle($purchase, $receiver);
            $this->fail('The second movement failure should have been raised.');
        } catch (RuntimeException $exception) {
            $this->assertSame($failureMessage, $exception->getMessage());
        } finally {
            InventoryMovement::flushEventListeners();
        }

        $this->assertDatabaseHas('purchases', [
            'id' => $purchase->id,
            'status' => PurchaseStatus::Pending->value,
            'received_at' => null,
        ]);
        $this->assertDatabaseHas('inventories', [
            'id' => $firstInventory->id,
            'stock' => 4,
        ]);
        $this->assertDatabaseHas('inventories', [
            'id' => $secondInventory->id,
            'stock' => 10,
        ]);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_received_purchase_cannot_be_received_or_edited_again(): void
    {
        $purchaser = $this->userWithRole(Role::PURCHASING);
        $warehouseUser = $this->userWithRole(Role::WAREHOUSE);
        $inventory = $this->inventoryWithStock(2);
        $purchase = $this->createDraft([
            ['product_id' => $inventory->product_id, 'quantity' => 4, 'unit_cost' => '8.00'],
        ]);
        $purchase->transitionTo(PurchaseStatus::Pending);
        app(ReceivePurchase::class)->handle($purchase, $warehouseUser);

        $this->actingAs($warehouseUser)
            ->post(route('compras.receive', $purchase))
            ->assertSessionHasErrors('purchase');

        $this->actingAs($purchaser)
            ->get(route('compras.edit', $purchase))
            ->assertNotFound();

        $this->assertDatabaseHas('inventories', [
            'id' => $inventory->id,
            'stock' => 6,
        ]);
        $this->assertDatabaseCount('inventory_movements', 1);
    }

    #[DataProvider('purchaseViewerRoles')]
    public function test_authorized_roles_can_view_purchase_lists(string $roleName): void
    {
        $user = $this->userWithRole($roleName);

        $this->actingAs($user)
            ->get(route('compras.index'))
            ->assertOk();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function purchaseViewerRoles(): array
    {
        return [
            'administrator' => [Role::ADMINISTRATOR],
            'purchasing' => [Role::PURCHASING],
            'warehouse' => [Role::WAREHOUSE],
        ];
    }

    public function test_user_without_purchase_permissions_cannot_access_purchase_pages(): void
    {
        $user = $this->userWithRole('Ventas');

        $this->actingAs($user)
            ->get(route('compras.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('compras.create'))
            ->assertForbidden();
    }

    public function test_warehouse_can_receive_but_cannot_create_purchase_orders(): void
    {
        $warehouseUser = $this->userWithRole(Role::WAREHOUSE);
        $supplier = Supplier::factory()->create();
        $inventory = $this->inventoryWithStock(0);

        $this->actingAs($warehouseUser)
            ->post(route('compras.store'), [
                'supplier_id' => $supplier->id,
                'order_date' => '2026-09-28',
                'details' => [
                    ['product_id' => $inventory->product_id, 'quantity' => 1, 'unit_cost' => '1.00'],
                ],
            ])
            ->assertForbidden();
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int, unit_cost: string}>  $details
     */
    private function createDraft(array $details): Purchase
    {
        return app(SavePurchaseDraft::class)->handle([
            'supplier_id' => Supplier::factory()->create()->id,
            'order_date' => '2026-09-28',
            'details' => $details,
        ]);
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
