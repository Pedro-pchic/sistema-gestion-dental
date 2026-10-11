<?php

namespace Tests\Feature;

use App\Actions\SaveQuoteDraft;
use App\Enums\QuoteStatus;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Quote;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class QuoteAdministrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_sales_user_creates_quote_with_server_calculated_totals_without_touching_inventory(): void
    {
        $user = $this->userWithRole(Role::SALES);
        $customer = Customer::factory()->create();
        $firstProduct = Product::factory()->create(['price' => '19.99']);
        $secondProduct = Product::factory()->create(['price' => '5.25']);
        $firstInventory = Inventory::factory()->for($firstProduct)->create(['stock' => 7]);
        $secondInventory = Inventory::factory()->for($secondProduct)->create(['stock' => 11]);

        $this->actingAs($user)
            ->post(route('cotizaciones.store'), [
                'customer_id' => $customer->id,
                'quote_date' => '2026-09-28',
                'valid_until' => '2026-10-15',
                'notes' => 'Oferta de temporada',
                'total' => '0.01',
                'details' => [
                    ['product_id' => $firstProduct->id, 'quantity' => 2, 'unit_price' => '12.00'],
                    ['product_id' => $secondProduct->id, 'quantity' => 3, 'unit_price' => '5.00'],
                ],
            ])
            ->assertRedirect();

        $quote = Quote::query()->with('details')->firstOrFail();

        $this->assertSame(QuoteStatus::Draft, $quote->status);
        $this->assertSame('39.00', $quote->total);
        $this->assertStringStartsWith('COT-', $quote->number);
        $this->assertDatabaseHas('quote_details', [
            'quote_id' => $quote->id,
            'product_id' => $firstProduct->id,
            'quantity' => 2,
            'unit_price' => '12.00',
            'subtotal' => '24.00',
        ]);
        $this->assertDatabaseHas('quote_details', [
            'quote_id' => $quote->id,
            'product_id' => $secondProduct->id,
            'quantity' => 3,
            'unit_price' => '5.00',
            'subtotal' => '15.00',
        ]);
        $this->assertDatabaseHas('inventories', ['id' => $firstInventory->id, 'stock' => 7]);
        $this->assertDatabaseHas('inventories', ['id' => $secondInventory->id, 'stock' => 11]);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_quote_requires_an_active_customer_and_at_least_one_product(): void
    {
        $user = $this->userWithRole(Role::ADMINISTRATOR);
        $inactiveCustomer = Customer::factory()->create(['is_active' => false]);
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->post(route('cotizaciones.store'), [
                'customer_id' => $inactiveCustomer->id,
                'quote_date' => '2026-09-28',
                'details' => [
                    ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => '3.00'],
                ],
            ])
            ->assertSessionHasErrors('customer_id');

        $activeCustomer = Customer::factory()->create();

        $this->actingAs($user)
            ->post(route('cotizaciones.store'), [
                'customer_id' => $activeCustomer->id,
                'quote_date' => '2026-09-28',
                'details' => [],
            ])
            ->assertSessionHasErrors('details');

        $this->assertDatabaseCount('quotes', 0);
    }

    public function test_quote_rejects_nonpositive_quantities_and_negative_prices(): void
    {
        $user = $this->userWithRole(Role::SALES);
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->post(route('cotizaciones.store'), [
                'customer_id' => $customer->id,
                'quote_date' => '2026-09-28',
                'details' => [
                    ['product_id' => $product->id, 'quantity' => 0, 'unit_price' => '-1.00'],
                ],
            ])
            ->assertSessionHasErrors(['details.0.quantity', 'details.0.unit_price']);

        $this->assertDatabaseCount('quotes', 0);
    }

    public function test_quote_form_uses_the_current_product_price_as_the_initial_quote_price(): void
    {
        $user = $this->userWithRole(Role::SALES);
        Customer::factory()->create(['name' => 'Cliente de prueba']);
        Product::factory()->create([
            'sku' => 'PRECIO-001',
            'name' => 'Producto con precio base',
            'price' => '24.99',
        ]);

        $this->actingAs($user)
            ->get(route('cotizaciones.create'))
            ->assertOk()
            ->assertSee('data-price="24.99"', false)
            ->assertSeeText('Producto con precio base');
    }

    public function test_authorized_user_can_view_quote_list_and_quote_details(): void
    {
        $user = $this->userWithRole(Role::SALES);
        $customer = Customer::factory()->create(['name' => 'Cliente de cotización']);
        $product = Product::factory()->create([
            'sku' => 'COT-VISTA-001',
            'name' => 'Producto visible en cotización',
        ]);
        $quote = $this->createDraft($customer, $product);

        $this->actingAs($user)
            ->get(route('cotizaciones.index'))
            ->assertOk()
            ->assertSeeText($quote->number)
            ->assertSeeText('Cliente de cotización');

        $this->actingAs($user)
            ->get(route('cotizaciones.show', $quote))
            ->assertOk()
            ->assertSeeText('Producto visible en cotización')
            ->assertSeeText('Q 20.00')
            ->assertSeeText('Borrador');
    }

    public function test_quote_keeps_its_unit_price_after_the_product_price_changes(): void
    {
        $user = $this->userWithRole(Role::SALES);
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => '24.99']);

        $this->actingAs($user)
            ->post(route('cotizaciones.store'), [
                'customer_id' => $customer->id,
                'quote_date' => '2026-09-28',
                'details' => [
                    ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => '20.00'],
                ],
            ])
            ->assertRedirect();

        $quote = Quote::query()->firstOrFail();
        $product->update(['price' => '80.00']);

        $this->assertDatabaseHas('quote_details', [
            'quote_id' => $quote->id,
            'product_id' => $product->id,
            'unit_price' => '20.00',
            'subtotal' => '40.00',
        ]);
    }

    public function test_quote_transitions_follow_allowed_paths_and_terminal_quotes_cannot_be_edited(): void
    {
        $user = $this->userWithRole(Role::SALES);
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();
        $acceptedQuote = $this->createDraft($customer, $product);
        $draftRejectedQuote = $this->createDraft($customer, $product);
        $sentRejectedQuote = $this->createDraft($customer, $product);

        $this->actingAs($user)
            ->post(route('cotizaciones.send', $acceptedQuote))
            ->assertRedirectToRoute('cotizaciones.show', $acceptedQuote);

        $this->assertDatabaseHas('quotes', ['id' => $acceptedQuote->id, 'status' => QuoteStatus::Sent->value]);

        $this->actingAs($user)
            ->post(route('cotizaciones.accept', $acceptedQuote))
            ->assertRedirectToRoute('cotizaciones.show', $acceptedQuote);

        $this->assertDatabaseHas('quotes', ['id' => $acceptedQuote->id, 'status' => QuoteStatus::Accepted->value]);

        $this->actingAs($user)
            ->post(route('cotizaciones.reject', $draftRejectedQuote))
            ->assertRedirectToRoute('cotizaciones.show', $draftRejectedQuote);

        $this->assertDatabaseHas('quotes', ['id' => $draftRejectedQuote->id, 'status' => QuoteStatus::Rejected->value]);

        $this->actingAs($user)
            ->post(route('cotizaciones.send', $sentRejectedQuote))
            ->assertRedirectToRoute('cotizaciones.show', $sentRejectedQuote);

        $this->actingAs($user)
            ->post(route('cotizaciones.reject', $sentRejectedQuote))
            ->assertRedirectToRoute('cotizaciones.show', $sentRejectedQuote);

        $this->assertDatabaseHas('quotes', ['id' => $sentRejectedQuote->id, 'status' => QuoteStatus::Rejected->value]);

        $this->actingAs($user)
            ->post(route('cotizaciones.accept', $draftRejectedQuote))
            ->assertSessionHasErrors('status');

        $this->actingAs($user)
            ->get(route('cotizaciones.edit', $acceptedQuote))
            ->assertNotFound();

        $this->actingAs($user)
            ->get(route('cotizaciones.edit', $draftRejectedQuote))
            ->assertNotFound();

        $this->actingAs($user)
            ->put(route('cotizaciones.update', $acceptedQuote), $this->quoteData($customer, $product))
            ->assertSessionHasErrors('quote');

        $this->assertDatabaseHas('quotes', [
            'id' => $acceptedQuote->id,
            'status' => QuoteStatus::Accepted->value,
            'total' => '20.00',
        ]);

        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_user_without_commercial_permission_cannot_access_quotes(): void
    {
        $user = $this->userWithRole(Role::PURCHASING);

        $this->actingAs($user)
            ->get(route('cotizaciones.index'))
            ->assertForbidden();
    }

    /**
     * @return array<string, mixed>
     */
    private function quoteData(Customer $customer, Product $product): array
    {
        return [
            'customer_id' => $customer->id,
            'quote_date' => '2026-09-28',
            'details' => [
                ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => '10.00'],
            ],
        ];
    }

    private function createDraft(Customer $customer, Product $product): Quote
    {
        return app(SaveQuoteDraft::class)->handle($this->quoteData($customer, $product));
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::factory()->create(['name' => $roleName]);

        return User::factory()->for($role)->create(['is_active' => true]);
    }
}
