<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PaymentAdministrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_partial_then_full_payments_update_invoice_status(): void
    {
        $user = $this->userWithRole(Role::FINANCE);
        $invoice = $this->invoiceWithTotal('100.00');

        $this->actingAs($user)
            ->post(route('facturas.pagos.store', $invoice), $this->paymentData('35.00'))
            ->assertRedirect();

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => InvoiceStatus::PartiallyPaid->value,
        ]);

        $this->actingAs($user)
            ->post(route('facturas.pagos.store', $invoice), $this->paymentData('65.00'))
            ->assertRedirect();

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => InvoiceStatus::Paid->value,
        ]);
        $this->assertDatabaseCount('payments', 2);
    }

    public function test_overpayment_is_rejected_without_writing_a_payment(): void
    {
        $user = $this->userWithRole(Role::FINANCE);
        $invoice = $this->invoiceWithTotal('100.00');

        $this->actingAs($user)
            ->post(route('facturas.pagos.store', $invoice), $this->paymentData('100.01'))
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => InvoiceStatus::Issued->value,
        ]);
    }

    public function test_decimal_payments_are_accumulated_without_float_rounding(): void
    {
        $user = $this->userWithRole(Role::FINANCE);
        $invoice = $this->invoiceWithTotal('1.00');

        foreach (['0.10', '0.20', '0.70'] as $amount) {
            $this->actingAs($user)
                ->post(route('facturas.pagos.store', $invoice), $this->paymentData($amount))
                ->assertRedirect();
        }

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => InvoiceStatus::Paid->value,
        ]);
        $this->assertDatabaseCount('payments', 3);
    }

    public function test_cancelled_invoice_cannot_accept_a_payment(): void
    {
        $user = $this->userWithRole(Role::FINANCE);
        $invoice = $this->invoiceWithTotal('100.00');
        $invoice->update(['status' => InvoiceStatus::Cancelled]);

        $this->actingAs($user)
            ->post(route('facturas.pagos.store', $invoice), $this->paymentData('10.00'))
            ->assertSessionHasErrors('invoice');

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_receipt_shows_payment_reference_customer_invoice_date_method_and_amount(): void
    {
        $user = $this->userWithRole(Role::FINANCE);
        $invoice = $this->invoiceWithTotal('100.00', 'Cliente del recibo');

        $this->actingAs($user)
            ->post(route('facturas.pagos.store', $invoice), $this->paymentData('12.50', 'transfer'))
            ->assertRedirect();

        $payment = $invoice->payments()->firstOrFail();

        $this->actingAs($user)
            ->get(route('pagos.receipt', $payment))
            ->assertOk()
            ->assertSeeText($payment->number)
            ->assertSeeText('Cliente del recibo')
            ->assertSeeText($invoice->number)
            ->assertSeeText('2026-09-28')
            ->assertSeeText('Transferencia')
            ->assertSeeText('12.50');

        $this->actingAs($user)
            ->get(route('facturas.show', $invoice))
            ->assertOk()
            ->assertSeeText($payment->number);

        $this->actingAs($user)
            ->get(route('pagos.show', $payment))
            ->assertOk()
            ->assertSeeText('Ver e imprimir recibo');
    }

    public function test_sales_user_cannot_manage_payments(): void
    {
        $user = $this->userWithRole(Role::SALES);
        $invoice = $this->invoiceWithTotal('100.00');

        $this->actingAs($user)
            ->post(route('facturas.pagos.store', $invoice), $this->paymentData('10.00'))
            ->assertForbidden();

        $this->assertDatabaseCount('payments', 0);
    }

    private function invoiceWithTotal(string $total, string $customerName = 'Cliente de prueba'): Invoice
    {
        $invoice = Invoice::factory()->create([
            'subtotal' => $total,
            'total' => $total,
        ]);
        $invoice->sale->customer->update(['name' => $customerName]);

        return $invoice;
    }

    /** @return array{amount: string, method: string, payment_date: string, notes: string} */
    private function paymentData(string $amount, string $method = 'cash'): array
    {
        return [
            'amount' => $amount,
            'method' => $method,
            'payment_date' => '2026-09-28',
            'notes' => '',
        ];
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::factory()->create(['name' => $roleName]);

        return User::factory()->for($role)->create(['is_active' => true]);
    }
}
