<?php

namespace Tests\Feature;

use App\Enums\CreditNoteStatus;
use App\Enums\InvoiceStatus;
use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CreditNoteAdministrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_valid_credit_note_reduces_balance_and_over_limit_note_is_rejected(): void
    {
        $user = $this->userWithRole(Role::FINANCE);
        $invoice = $this->invoiceWithTotal('100.00');

        $this->actingAs($user)
            ->post(route('facturas.notas-credito.store', $invoice), $this->creditNoteData('40.00'))
            ->assertRedirect();

        $creditNote = $invoice->creditNotes()->firstOrFail();

        $this->assertDatabaseHas('credit_notes', [
            'id' => $creditNote->id,
            'invoice_id' => $invoice->id,
            'amount' => '40.00',
            'reason' => 'Ajuste autorizado',
            'status' => CreditNoteStatus::Issued->value,
        ]);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => InvoiceStatus::PartiallyPaid->value,
        ]);

        $this->actingAs($user)
            ->post(route('facturas.notas-credito.store', $invoice), $this->creditNoteData('60.01'))
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('credit_notes', 1);

        $this->actingAs($user)
            ->get(route('notas-credito.print', $creditNote))
            ->assertOk()
            ->assertSeeText($creditNote->number)
            ->assertSeeText('Ajuste autorizado')
            ->assertSeeText('40.00');

        $this->actingAs($user)
            ->get(route('notas-credito.show', $creditNote))
            ->assertOk()
            ->assertSeeText($creditNote->number);
    }

    public function test_cancelling_a_credit_note_preserves_it_and_restores_invoice_balance(): void
    {
        $user = $this->userWithRole(Role::FINANCE);
        $invoice = $this->invoiceWithTotal('100.00');
        $creditNote = CreditNote::factory()->for($invoice)->create(['amount' => '25.00']);
        $invoice->update(['status' => InvoiceStatus::PartiallyPaid]);

        $this->actingAs($user)
            ->post(route('notas-credito.cancel', $creditNote))
            ->assertRedirect();

        $this->assertDatabaseHas('credit_notes', [
            'id' => $creditNote->id,
            'status' => CreditNoteStatus::Cancelled->value,
        ]);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => InvoiceStatus::Issued->value,
        ]);
        $this->assertDatabaseCount('credit_notes', 1);
    }

    public function test_credit_note_uses_remaining_balance_after_payments(): void
    {
        $user = $this->userWithRole(Role::FINANCE);
        $invoice = $this->invoiceWithTotal('100.00');
        Payment::factory()->for($invoice)->create(['amount' => '70.00']);
        $invoice->update(['status' => InvoiceStatus::PartiallyPaid]);

        $this->actingAs($user)
            ->post(route('facturas.notas-credito.store', $invoice), $this->creditNoteData('30.00'))
            ->assertRedirect();

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => InvoiceStatus::Paid->value,
        ]);

        $this->actingAs($user)
            ->post(route('facturas.notas-credito.store', $invoice), $this->creditNoteData('0.01'))
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('credit_notes', 1);
    }

    public function test_sales_user_cannot_manage_credit_notes(): void
    {
        $user = $this->userWithRole(Role::SALES);
        $invoice = $this->invoiceWithTotal('100.00');

        $this->actingAs($user)
            ->post(route('facturas.notas-credito.store', $invoice), $this->creditNoteData('10.00'))
            ->assertForbidden();

        $this->assertDatabaseCount('credit_notes', 0);
    }

    private function invoiceWithTotal(string $total): Invoice
    {
        return Invoice::factory()->create([
            'subtotal' => $total,
            'total' => $total,
        ]);
    }

    /** @return array{amount: string, reason: string, issue_date: string} */
    private function creditNoteData(string $amount): array
    {
        return [
            'amount' => $amount,
            'reason' => 'Ajuste autorizado',
            'issue_date' => '2026-09-28',
        ];
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::factory()->create(['name' => $roleName]);

        return User::factory()->for($role)->create(['is_active' => true]);
    }
}
