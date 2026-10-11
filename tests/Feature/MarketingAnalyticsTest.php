<?php

namespace Tests\Feature;

use App\Actions\LoadCart;
use App\Enums\EcommerceEventType;
use App\Enums\SaleStatus;
use App\Models\EcommerceEvent;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class MarketingAnalyticsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_portal_visit_and_consecutive_product_refreshes_are_recorded_once_per_session(): void
    {
        $product = $this->productWithStock(5);

        $this->get(route('inicio'))->assertOk();
        $this->get(route('catalogo.show', $product))->assertOk();
        $this->get(route('catalogo.show', $product))->assertOk();

        $this->assertDatabaseCount('ecommerce_events', 2);
        $this->assertDatabaseHas('ecommerce_events', [
            'event_type' => EcommerceEventType::PortalVisit->value,
            'product_id' => null,
        ]);
        $this->assertDatabaseHas('ecommerce_events', [
            'event_type' => EcommerceEventType::ProductViewed->value,
            'product_id' => $product->id,
        ]);
    }

    public function test_cart_and_checkout_events_do_not_change_the_cart_and_completed_order_is_recorded_once(): void
    {
        $product = $this->productWithStock(5);

        $this->post(route('carrito.store', $product), ['quantity' => 1])
            ->assertRedirectToRoute('carrito.index');
        $this->post(route('carrito.store', $product), ['quantity' => 1])
            ->assertRedirectToRoute('carrito.index')
            ->assertSessionHas(LoadCart::SESSION_KEY, [$product->id => 2]);

        $this->get(route('checkout.create'))->assertOk();
        $this->get(route('checkout.create'))->assertOk();

        $response = $this->post(route('checkout.store'), $this->checkoutPayload());
        $response->assertRedirect()->assertSessionMissing(LoadCart::SESSION_KEY);

        $confirmationUrl = $response->headers->get('Location');
        $this->get($confirmationUrl)->assertOk();
        $this->get($confirmationUrl)->assertOk();

        $this->assertDatabaseCount('ecommerce_events', 3);
        $this->assertDatabaseHas('ecommerce_events', [
            'event_type' => EcommerceEventType::CartStarted->value,
            'product_id' => null,
        ]);
        $this->assertDatabaseHas('ecommerce_events', [
            'event_type' => EcommerceEventType::CheckoutStarted->value,
            'product_id' => null,
        ]);
        $order = Order::query()->firstOrFail();
        $this->assertDatabaseHas('ecommerce_events', [
            'event_type' => EcommerceEventType::OrderCompleted->value,
            'order_id' => $order->id,
        ]);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_details', 1);
    }

    public function test_analytics_aggregates_filtered_events_and_confirmed_sales(): void
    {
        $role = Role::factory()->create(['name' => Role::SALES]);
        $user = User::factory()->for($role)->create(['is_active' => true]);
        $viewedProduct = Product::factory()->create(['name' => 'Bota más vista']);
        $otherProduct = Product::factory()->create(['name' => 'Sandalia vista']);
        $soldOnlyProduct = Product::factory()->create(['name' => 'Producto vendido confirmado']);
        $cancelledOnlyProduct = Product::factory()->create(['name' => 'Producto solo cancelado']);
        $outsideProduct = Product::factory()->create(['name' => 'Producto fuera del período']);
        $sameSession = str_repeat('a', 64);
        $secondSession = str_repeat('b', 64);

        EcommerceEvent::factory()->count(2)->create([
            'event_type' => EcommerceEventType::PortalVisit,
            'session_identifier' => $sameSession,
            'occurred_at' => '2026-06-10 10:00:00',
        ]);
        EcommerceEvent::factory()->create([
            'event_type' => EcommerceEventType::PortalVisit,
            'session_identifier' => $secondSession,
            'occurred_at' => '2026-06-11 10:00:00',
        ]);
        EcommerceEvent::factory()->create([
            'event_type' => EcommerceEventType::PortalVisit,
            'occurred_at' => '2026-05-31 10:00:00',
        ]);

        EcommerceEvent::factory()->count(3)->create([
            'event_type' => EcommerceEventType::ProductViewed,
            'product_id' => $viewedProduct->id,
            'occurred_at' => '2026-06-12 10:00:00',
        ]);
        EcommerceEvent::factory()->create([
            'event_type' => EcommerceEventType::ProductViewed,
            'product_id' => $otherProduct->id,
            'occurred_at' => '2026-06-12 10:00:00',
        ]);
        EcommerceEvent::factory()->create([
            'event_type' => EcommerceEventType::ProductViewed,
            'product_id' => $outsideProduct->id,
            'occurred_at' => '2026-05-31 10:00:00',
        ]);

        EcommerceEvent::factory()->count(4)->create([
            'event_type' => EcommerceEventType::CartStarted,
            'occurred_at' => '2026-06-13 10:00:00',
        ]);
        EcommerceEvent::factory()->count(3)->create([
            'event_type' => EcommerceEventType::CheckoutStarted,
            'occurred_at' => '2026-06-13 11:00:00',
        ]);
        $order = Order::factory()->create();
        EcommerceEvent::factory()->create([
            'event_type' => EcommerceEventType::OrderCompleted,
            'order_id' => $order->id,
            'occurred_at' => '2026-06-14 10:00:00',
        ]);

        $confirmedSale = Sale::factory()->create([
            'sale_date' => '2026-06-14',
            'status' => SaleStatus::Confirmed,
        ]);
        SaleDetail::factory()->for($confirmedSale)->for($soldOnlyProduct)->create(['quantity' => 4]);

        $cancelledSale = Sale::factory()->create([
            'sale_date' => '2026-06-14',
            'status' => SaleStatus::Cancelled,
        ]);
        SaleDetail::factory()->for($cancelledSale)->for($cancelledOnlyProduct)->create(['quantity' => 20]);

        $outsideSale = Sale::factory()->create(['sale_date' => '2026-05-31']);
        SaleDetail::factory()->for($outsideSale)->for($outsideProduct)->create(['quantity' => 30]);

        $this->actingAs($user)
            ->get(route('marketing.analytics.index', [
                'date_from' => '2026-06-01',
                'date_to' => '2026-06-30',
            ]))
            ->assertOk()
            ->assertSeeText('50.0%')
            ->assertSeeText('75.0%')
            ->assertSeeText($viewedProduct->name)
            ->assertSeeText($otherProduct->name)
            ->assertSeeText($soldOnlyProduct->name)
            ->assertDontSeeText($cancelledOnlyProduct->name)
            ->assertDontSeeText($outsideProduct->name);
    }

    public function test_analytics_returns_zero_rates_when_the_period_has_no_events(): void
    {
        $user = User::factory()
            ->for(Role::factory()->create(['name' => Role::SALES]))
            ->create(['is_active' => true]);

        $this->actingAs($user)
            ->get(route('marketing.analytics.index', [
                'date_from' => '2026-06-01',
                'date_to' => '2026-06-30',
            ]))
            ->assertOk()
            ->assertSeeText('0.0%');
    }

    public function test_users_without_commercial_access_cannot_view_analytics(): void
    {
        $user = User::factory()
            ->for(Role::factory()->create(['name' => Role::WAREHOUSE]))
            ->create(['is_active' => true]);

        $this->actingAs($user)
            ->get(route('marketing.analytics.index'))
            ->assertForbidden();
    }

    private function productWithStock(int $stock): Product
    {
        $product = Product::factory()->create();
        Inventory::factory()->for($product)->create(['stock' => $stock]);

        return $product;
    }

    /**
     * @return array<string, string>
     */
    private function checkoutPayload(): array
    {
        return [
            'customer_name' => 'Cliente de analítica',
            'customer_nit' => '1234567-8',
            'customer_phone' => '5555-0101',
            'customer_email' => 'analitica@example.test',
            'address_label' => 'Casa',
            'address' => '1 avenida 2-30, zona 4',
            'city' => 'Guatemala',
            'department' => 'Guatemala',
            'notes' => 'Entrega de prueba.',
        ];
    }
}
