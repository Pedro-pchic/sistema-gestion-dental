<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\SaleStatus;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class InvoiceAdministrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_confirmed_sale_can_be_invoiced_once_with_historical_amounts_without_changing_inventory(): void
    {
        $user = $this->userWithRole(Role::SALES);
        $customer = Customer::factory()->create(['name' => 'Cliente de factura']);
        $product = Product::factory()->create(['price' => '75.00']);
        $inventory = Inventory::factory()->for($product)->create(['stock' => 8]);
        $sale = Sale::factory()->for($customer)->create(['total' => '40.00']);
        SaleDetail::factory()->for($sale)->for($product)->create([
            'quantity' => 2,
            'unit_price' => '20.00',
            'subtotal' => '40.00',
        ]);
        $product->update(['price' => '99.00']);

        $this->actingAs($user)
            ->post(route('ventas.factura.store', $sale))
            ->assertRedirect();

        $invoice = $sale->invoice()->firstOrFail();

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'sale_id' => $sale->id,
            'status' => InvoiceStatus::Issued->value,
            'subtotal' => '40.00',
            'total' => '40.00',
        ]);
        $this->assertDatabaseHas('inventories', ['id' => $inventory->id, 'stock' => 8]);
        $this->assertDatabaseCount('inventory_movements', 0);

        $this->actingAs($user)
            ->post(route('ventas.factura.store', $sale))
            ->assertSessionHasErrors('sale');

        $this->assertDatabaseCount('invoices', 1);

        $this->actingAs($user)
            ->get(route('facturas.show', $invoice))
            ->assertOk()
            ->assertSeeText('Factura de Cliente de factura');

        $this->actingAs($user)
            ->get(route('facturas.print', $invoice))
            ->assertOk()
            ->assertSeeText('20.00')
            ->assertSeeText('40.00')
            ->assertDontSeeText('99.00');
    }

    public function test_cancelled_sale_cannot_be_invoiced(): void
    {
        $user = $this->userWithRole(Role::SALES);
        $sale = Sale::factory()->create(['status' => SaleStatus::Cancelled, 'total' => '1.00']);
        $product = Product::factory()->create();
        SaleDetail::factory()->for($sale)->for($product)->create([
            'quantity' => 1,
            'unit_price' => '1.00',
            'subtotal' => '1.00',
        ]);

        $this->actingAs($user)
            ->post(route('ventas.factura.store', $sale))
            ->assertSessionHasErrors('sale');

        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_user_without_invoice_permission_cannot_issue_or_view_invoices(): void
    {
        $user = $this->userWithRole(Role::PURCHASING);
        $sale = Sale::factory()->create();

        $this->actingAs($user)
            ->post(route('ventas.factura.store', $sale))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('facturas.index'))
            ->assertForbidden();

        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_unpaid_invoice_can_be_cancelled_and_kept_in_history(): void
    {
        $user = $this->userWithRole(Role::FINANCE);
        $invoice = Invoice::factory()->create();

        $this->actingAs($user)
            ->post(route('facturas.cancel', $invoice))
            ->assertRedirect();

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => InvoiceStatus::Cancelled->value,
        ]);
        $this->assertDatabaseCount('invoices', 1);
    }

    public function test_invoice_with_payment_cannot_be_cancelled_or_removed(): void
    {
        $user = $this->userWithRole(Role::FINANCE);
        $invoice = Invoice::factory()->create();
        Payment::factory()->for($invoice)->create();

        $this->actingAs($user)
            ->post(route('facturas.cancel', $invoice))
            ->assertSessionHasErrors('invoice');

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => InvoiceStatus::Issued->value,
        ]);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('payments', 1);
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::factory()->create(['name' => $roleName]);

        return User::factory()->for($role)->create(['is_active' => true]);
    }
}
