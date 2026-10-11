<?php

namespace Tests\Feature;

use App\Actions\LoadCart;
use App\Enums\OrderStatus;
use App\Models\Address;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class EcommerceFlowTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_catalog_shows_only_active_products_from_active_categories(): void
    {
        $activeCategory = Category::factory()->create(['name' => 'Calzado activo']);
        $inactiveCategory = Category::factory()->create(['name' => 'Calzado oculto', 'is_active' => false]);
        $visibleProduct = $this->productWithStock(5, [
            'category_id' => $activeCategory->id,
            'name' => 'Zapato visible',
            'sku' => 'WEB-001',
        ]);
        $inactiveProduct = Product::factory()->for($activeCategory)->create([
            'name' => 'Zapato inactivo',
            'is_active' => false,
        ]);
        $hiddenCategoryProduct = Product::factory()->for($inactiveCategory)->create([
            'name' => 'Zapato de categoría inactiva',
        ]);

        $this->get(route('catalogo.index'))
            ->assertOk()
            ->assertSeeText($visibleProduct->name)
            ->assertSeeText('Disponible')
            ->assertDontSeeText($inactiveProduct->name)
            ->assertDontSeeText($hiddenCategoryProduct->name);
    }

    public function test_inactive_product_cannot_be_added_to_cart(): void
    {
        $product = $this->productWithStock(5, ['is_active' => false]);

        $this->post(route('carrito.store', $product), ['quantity' => 1])
            ->assertSessionHasErrors([
                'product' => 'El producto ya no está disponible para compra.',
            ])
            ->assertSessionMissing(LoadCart::SESSION_KEY);
    }

    public function test_out_of_stock_product_cannot_be_added_to_cart(): void
    {
        $product = $this->productWithStock(0);

        $this->post(route('carrito.store', $product), ['quantity' => 1])
            ->assertSessionHasErrors([
                'quantity' => 'No hay existencias suficientes para la cantidad solicitada.',
            ])
            ->assertSessionMissing(LoadCart::SESSION_KEY);
    }

    public function test_cart_rejects_a_non_positive_quantity(): void
    {
        $product = $this->productWithStock(5);

        $this->post(route('carrito.store', $product), ['quantity' => 0])
            ->assertSessionHasErrors([
                'quantity' => 'La cantidad debe ser mayor que cero.',
            ])
            ->assertSessionMissing(LoadCart::SESSION_KEY);
    }

    public function test_cart_adds_updates_removes_and_clears_products(): void
    {
        $product = $this->productWithStock(10);

        $this->post(route('carrito.store', $product), ['quantity' => 2])
            ->assertRedirectToRoute('carrito.index')
            ->assertSessionHas(LoadCart::SESSION_KEY, [$product->id => 2]);

        $this->patch(route('carrito.update', $product), ['quantity' => 3])
            ->assertRedirect()
            ->assertSessionHas(LoadCart::SESSION_KEY, [$product->id => 3]);

        $this->delete(route('carrito.destroy', $product))
            ->assertRedirect()
            ->assertSessionHas(LoadCart::SESSION_KEY, []);

        $this->post(route('carrito.store', $product), ['quantity' => 1])
            ->assertSessionHas(LoadCart::SESSION_KEY, [$product->id => 1]);

        $this->delete(route('carrito.clear'))
            ->assertRedirect()
            ->assertSessionMissing(LoadCart::SESSION_KEY);
    }

    public function test_checkout_recalculates_prices_and_creates_only_a_pending_order(): void
    {
        $customer = Customer::factory()->create(['email' => 'cliente@example.test']);
        $address = Address::factory()->for($customer)->create([
            'address' => '10 avenida 1-20, zona 1',
            'city' => 'Guatemala',
            'department' => 'Guatemala',
        ]);
        $product = $this->productWithStock(5, ['price' => '10.00']);
        $inventory = $product->inventory;

        $this->post(route('carrito.store', $product), ['quantity' => 2])
            ->assertRedirectToRoute('carrito.index');

        $product->update(['price' => '15.00']);

        $response = $this->post(route('checkout.store'), $this->checkoutPayload([
            'customer_name' => 'Nombre no sobrescrito',
            'customer_email' => 'CLIENTE@example.test',
            'address' => $address->address,
            'city' => $address->city,
            'department' => $address->department,
            'total' => '0.01',
            'unit_price' => '0.01',
        ]));

        $response->assertRedirect()->assertSessionMissing(LoadCart::SESSION_KEY);

        $order = Order::query()->firstOrFail();
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'customer_id' => $customer->id,
            'address_id' => $address->id,
            'origin' => 'web',
            'status' => OrderStatus::Pending->value,
            'total' => '30.00',
        ]);
        $this->assertDatabaseHas('order_details', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => '15.00',
            'subtotal' => '30.00',
        ]);
        $this->assertDatabaseHas('inventories', ['id' => $inventory->id, 'stock' => 5]);
        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseCount('addresses', 1);
        $this->assertDatabaseCount('inventory_movements', 0);
        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('payments', 0);

        $this->get($response->headers->get('Location'))
            ->assertOk()
            ->assertSeeText($order->number)
            ->assertSeeText('Pendiente')
            ->assertSeeText('10 avenida 1-20, zona 1')
            ->assertSeeText('Q 30.00');
    }

    public function test_checkout_rejects_insufficient_stock_and_keeps_the_cart(): void
    {
        $product = $this->productWithStock(5);
        $inventory = $product->inventory;

        $this->post(route('carrito.store', $product), ['quantity' => 3]);
        $inventory->update(['stock' => 2]);

        $this->post(route('checkout.store'), $this->checkoutPayload())
            ->assertSessionHasErrors([
                'cart' => "Existencia insuficiente para el producto {$product->sku}.",
            ])
            ->assertSessionHas(LoadCart::SESSION_KEY, [$product->id => 3]);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_details', 0);
        $this->assertDatabaseHas('inventories', ['id' => $inventory->id, 'stock' => 2]);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_checkout_failure_rolls_back_customer_address_and_order(): void
    {
        $product = $this->productWithStock(5);
        $failureMessage = 'Order detail could not be saved.';

        OrderDetail::creating(static function () use ($failureMessage): void {
            throw new RuntimeException($failureMessage);
        });

        try {
            $this->withSession([LoadCart::SESSION_KEY => [$product->id => 1]])
                ->post(route('checkout.store'), $this->checkoutPayload())
                ->assertServerError();
        } finally {
            OrderDetail::flushEventListeners();
        }

        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('addresses', 0);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_details', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function productWithStock(int $stock, array $attributes = []): Product
    {
        $product = Product::factory()->create($attributes);
        Inventory::factory()->for($product)->create([
            'stock' => $stock,
            'minimum_stock' => 1,
        ]);

        return $product->load(['category', 'inventory']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function checkoutPayload(array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'Cliente web',
            'customer_nit' => '1234567-8',
            'customer_phone' => '5555-0101',
            'customer_email' => 'cliente-web@example.test',
            'address_label' => 'Casa',
            'address' => '1 avenida 2-30, zona 4',
            'city' => 'Guatemala',
            'department' => 'Guatemala',
            'notes' => 'Entregar por la tarde.',
        ], $overrides);
    }
}
