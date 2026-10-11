<?php

namespace Tests\Feature;

use App\Actions\SaveOrder;
use App\Enums\OrderStatus;
use App\Enums\QuoteStatus;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\Quote;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OrderAdministrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_sales_user_can_create_a_pending_order_with_server_calculated_totals_without_changing_stock(): void
    {
        $user = $this->userWithRole(Role::SALES);
        $customer = Customer::factory()->create();
        $inventory = Inventory::factory()->for(Product::factory())->create(['stock' => 9]);

        $this->actingAs($user)
            ->get(route('pedidos.create'))
            ->assertOk()
            ->assertSeeText('Crear pedido pendiente');

        $this->actingAs($user)
            ->post(route('pedidos.store'), [
                'customer_id' => $customer->id,
                'order_date' => '2026-09-28',
                'total' => '0.01',
                'details' => [[
                    'product_id' => $inventory->product_id,
                    'quantity' => 3,
                    'unit_price' => '12.34',
                ]],
            ])
            ->assertRedirect();

        $order = Order::query()->firstOrFail();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'customer_id' => $customer->id,
            'quote_id' => null,
            'status' => OrderStatus::Pending->value,
            'total' => '37.02',
        ]);
        $this->assertDatabaseHas('order_details', [
            'order_id' => $order->id,
            'product_id' => $inventory->product_id,
            'quantity' => 3,
            'unit_price' => '12.34',
            'subtotal' => '37.02',
        ]);
        $this->assertDatabaseHas('inventories', ['id' => $inventory->id, 'stock' => 9]);
        $this->assertDatabaseCount('inventory_movements', 0);

        $this->actingAs($user)
            ->get(route('pedidos.edit', $order))
            ->assertOk()
            ->assertSeeText('Solo los pedidos pendientes se pueden editar');

        $this->actingAs($user)
            ->get(route('pedidos.show', $order))
            ->assertOk()
            ->assertSeeText($order->number)
            ->assertSeeText('Pendiente');
    }

    public function test_accepted_quote_converts_once_and_keeps_customer_and_quoted_prices(): void
    {
        $user = $this->userWithRole(Role::SALES);
        $customer = Customer::factory()->create();
        $firstProduct = Product::factory()->create(['price' => '91.00']);
        $secondProduct = Product::factory()->create(['price' => '120.00']);
        $quote = Quote::factory()->create([
            'customer_id' => $customer->id,
            'status' => QuoteStatus::Accepted,
            'total' => '0.00',
            'notes' => 'Precio acordado',
        ]);
        $quote->details()->createMany([
            ['product_id' => $firstProduct->id, 'quantity' => 2, 'unit_price' => '18.75', 'subtotal' => '37.50'],
            ['product_id' => $secondProduct->id, 'quantity' => 1, 'unit_price' => '44.20', 'subtotal' => '44.20'],
        ]);

        $this->actingAs($user)
            ->post(route('cotizaciones.pedido.store', $quote))
            ->assertRedirect();

        $order = Order::query()->firstOrFail();
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'customer_id' => $customer->id,
            'quote_id' => $quote->id,
            'total' => '81.70',
            'notes' => 'Precio acordado',
        ]);
        $this->assertDatabaseHas('order_details', [
            'order_id' => $order->id,
            'product_id' => $firstProduct->id,
            'unit_price' => '18.75',
            'subtotal' => '37.50',
        ]);
        $this->assertDatabaseHas('order_details', [
            'order_id' => $order->id,
            'product_id' => $secondProduct->id,
            'unit_price' => '44.20',
            'subtotal' => '44.20',
        ]);

        $this->actingAs($user)
            ->post(route('cotizaciones.pedido.store', $quote))
            ->assertSessionHasErrors('quote');

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_details', 2);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_confirmed_order_cannot_be_edited_and_a_cancelled_order_cannot_be_sold(): void
    {
        $user = $this->userWithRole(Role::SALES);
        $customer = Customer::factory()->create();
        $inventory = Inventory::factory()->for(Product::factory())->create(['stock' => 8]);
        $order = $this->createOrder($customer, $inventory->product, '2', '10.00');

        $this->actingAs($user)
            ->post(route('pedidos.confirm', $order))
            ->assertRedirectToRoute('pedidos.show', $order);

        $this->actingAs($user)
            ->get(route('pedidos.edit', $order))
            ->assertNotFound();

        $this->actingAs($user)
            ->put(route('pedidos.update', $order), [
                'customer_id' => $customer->id,
                'order_date' => '2026-09-28',
                'details' => [[
                    'product_id' => $inventory->product_id,
                    'quantity' => 4,
                    'unit_price' => '8.00',
                ]],
            ])
            ->assertSessionHasErrors('order');

        $this->actingAs($user)
            ->post(route('pedidos.cancel', $order))
            ->assertRedirectToRoute('pedidos.show', $order);

        $this->actingAs($user)
            ->post(route('pedidos.sale.store', $order))
            ->assertSessionHasErrors('order');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => OrderStatus::Cancelled->value]);
        $this->assertDatabaseHas('inventories', ['id' => $inventory->id, 'stock' => 8]);
        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_pending_order_can_be_edited_and_recalculates_its_total(): void
    {
        $user = $this->userWithRole(Role::SALES);
        $customer = Customer::factory()->create();
        $inventory = Inventory::factory()->for(Product::factory())->create(['stock' => 7]);
        $order = $this->createOrder($customer, $inventory->product, '1', '2.00');

        $this->actingAs($user)
            ->put(route('pedidos.update', $order), [
                'customer_id' => $customer->id,
                'order_date' => '2026-09-28',
                'details' => [[
                    'product_id' => $inventory->product_id,
                    'quantity' => 3,
                    'unit_price' => '4.25',
                ]],
            ])
            ->assertRedirectToRoute('pedidos.show', $order);

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'total' => '12.75']);
        $this->assertDatabaseHas('order_details', [
            'order_id' => $order->id,
            'product_id' => $inventory->product_id,
            'quantity' => 3,
            'unit_price' => '4.25',
            'subtotal' => '12.75',
        ]);
        $this->assertDatabaseHas('inventories', ['id' => $inventory->id, 'stock' => 7]);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_inactive_customer_cannot_receive_a_new_order(): void
    {
        $user = $this->userWithRole(Role::SALES);
        $customer = Customer::factory()->create(['is_active' => false]);
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->post(route('pedidos.store'), [
                'customer_id' => $customer->id,
                'order_date' => '2026-09-28',
                'details' => [[
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => '1.00',
                ]],
            ])
            ->assertSessionHasErrors('customer_id');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_details', 0);
    }

    #[DataProvider('commercialRoles')]
    public function test_administrator_and_sales_can_view_orders_and_sales(string $roleName): void
    {
        $user = $this->userWithRole($roleName);

        $this->actingAs($user)
            ->get(route('pedidos.index'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('ventas.index'))
            ->assertOk();
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

    #[DataProvider('nonCommercialRoles')]
    public function test_user_without_commercial_permission_cannot_manage_orders_or_sales(string $roleName): void
    {
        $user = $this->userWithRole($roleName);

        $this->actingAs($user)
            ->get(route('pedidos.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('pedidos.store'), [])
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('ventas.index'))
            ->assertForbidden();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function nonCommercialRoles(): array
    {
        return [
            'purchasing' => [Role::PURCHASING],
            'warehouse' => [Role::WAREHOUSE],
        ];
    }

    private function createOrder(Customer $customer, Product $product, string $quantity, string $unitPrice): Order
    {
        return app(SaveOrder::class)->handle([
            'customer_id' => $customer->id,
            'order_date' => '2026-09-28',
            'details' => [
                ['product_id' => $product->id, 'quantity' => $quantity, 'unit_price' => $unitPrice],
            ],
        ]);
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::factory()->create(['name' => $roleName]);

        return User::factory()->for($role)->create(['is_active' => true]);
    }
}
