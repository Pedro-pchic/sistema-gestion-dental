<?php

namespace Tests\Feature;

use App\Actions\RecordPaymentIncome;
use App\Enums\FinancialCategory;
use App\Enums\PurchaseStatus;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FinanceAdministrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_registered_payment_creates_one_traced_income_idempotently_without_inventory_changes(): void
    {
        $user = $this->userWithRole(Role::FINANCE);
        $invoice = Invoice::factory()->create([
            'total' => '100.00',
        ]);

        $this->actingAs($user)
            ->post(route('facturas.pagos.store', $invoice), [
                'amount' => '35.00',
                'payment_date' => '2026-09-15',
                'method' => 'cash',
            ])
            ->assertRedirect();

        $payment = Payment::query()->sole();
        $income = Income::query()->sole();

        $this->assertSame($payment->id, $income->payment_id);
        $this->assertSame($income->id, $payment->income->id);
        $this->assertSame(FinancialCategory::Sales, $income->category);
        $this->assertSame('2026-09-15', $income->date->toDateString());
        $this->assertSame('35.00', $income->amount);

        $firstResult = app(RecordPaymentIncome::class)->handle($payment);
        $secondResult = app(RecordPaymentIncome::class)->handle($payment);

        $this->assertSame($income->id, $firstResult->id);
        $this->assertSame($income->id, $secondResult->id);
        $this->assertDatabaseCount('incomes', 1);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_manual_income_is_recorded_with_other_income_category(): void
    {
        $user = $this->userWithRole(Role::FINANCE);

        $this->actingAs($user)
            ->post(route('finanzas.ingresos.store'), [
                'date' => '2026-09-18',
                'amount' => '125.50',
                'description' => 'Aporte extraordinario',
            ])
            ->assertRedirect(route('finanzas.ingresos.index'));

        $income = Income::query()->sole();

        $this->assertNull($income->payment_id);
        $this->assertSame(FinancialCategory::OtherIncome, $income->category);
        $this->assertSame('2026-09-18', $income->date->toDateString());
        $this->assertSame('125.50', $income->amount);
        $this->assertSame('Aporte extraordinario', $income->description);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_manual_expense_is_recorded_with_selected_category(): void
    {
        $user = $this->userWithRole(Role::FINANCE);

        $this->actingAs($user)
            ->post(route('finanzas.gastos.store'), [
                'date' => '2026-09-19',
                'amount' => '40.00',
                'category' => FinancialCategory::Operating->value,
                'description' => 'Servicio de mensajería',
            ])
            ->assertRedirect(route('finanzas.gastos.index'));

        $expense = Expense::query()->sole();

        $this->assertNull($expense->purchase_id);
        $this->assertSame(FinancialCategory::Operating, $expense->category);
        $this->assertSame('2026-09-19', $expense->date->toDateString());
        $this->assertSame('40.00', $expense->amount);
        $this->assertSame('Servicio de mensajería', $expense->description);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_received_purchase_expense_uses_server_total_and_keeps_traceability_once(): void
    {
        $user = $this->userWithRole(Role::FINANCE);
        $purchase = Purchase::factory()->create([
            'status' => PurchaseStatus::Received,
            'total' => '55.75',
            'received_at' => now(),
        ]);

        $payload = [
            'purchase_id' => $purchase->id,
            'date' => '2026-09-20',
            'amount' => '0.01',
            'category' => FinancialCategory::OtherExpense->value,
            'description' => 'Pago de compra recibida',
        ];

        $this->actingAs($user)
            ->post(route('finanzas.gastos.store'), $payload)
            ->assertRedirect(route('finanzas.gastos.index'));

        $expense = Expense::query()->sole();

        $this->assertSame($purchase->id, $expense->purchase_id);
        $this->assertSame(FinancialCategory::Purchases, $expense->category);
        $this->assertSame('55.75', $expense->amount);

        $this->actingAs($user)
            ->post(route('finanzas.gastos.store'), $payload)
            ->assertSessionHasErrors('purchase_id');

        $this->assertDatabaseCount('expenses', 1);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    #[DataProvider('nonPositiveAmountProvider')]
    public function test_non_positive_manual_amounts_are_rejected(string $routeName, array $payload, string $table): void
    {
        $user = $this->userWithRole(Role::FINANCE);

        $this->actingAs($user)
            ->post(route($routeName), $payload)
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount($table, 0);
    }

    public static function nonPositiveAmountProvider(): array
    {
        return [
            'zero income' => [
                'finanzas.ingresos.store',
                [
                    'date' => '2026-09-21',
                    'amount' => '0',
                    'description' => 'Ingreso inválido',
                ],
                'incomes',
            ],
            'negative expense' => [
                'finanzas.gastos.store',
                [
                    'date' => '2026-09-21',
                    'amount' => '-1',
                    'category' => FinancialCategory::Operating->value,
                    'description' => 'Gasto inválido',
                ],
                'expenses',
            ],
        ];
    }

    public function test_summary_calculates_totals_and_filters_history_by_date_range(): void
    {
        $user = $this->userWithRole(Role::FINANCE);

        Income::factory()->create([
            'date' => '2026-09-05',
            'amount' => '100.00',
            'description' => 'Ingreso incluido uno',
        ]);
        Income::factory()->create([
            'date' => '2026-09-10',
            'amount' => '50.00',
            'description' => 'Ingreso incluido dos',
        ]);
        Income::factory()->create([
            'date' => '2026-08-31',
            'amount' => '999.00',
            'description' => 'Ingreso excluido',
        ]);
        Expense::factory()->create([
            'date' => '2026-09-15',
            'amount' => '40.00',
            'description' => 'Gasto incluido',
        ]);
        Expense::factory()->create([
            'date' => '2026-10-01',
            'amount' => '888.00',
            'description' => 'Gasto excluido',
        ]);

        $this->actingAs($user)
            ->get(route('finanzas.index', [
                'from' => '2026-09-01',
                'to' => '2026-09-30',
            ]))
            ->assertOk()
            ->assertViewHas('incomeTotal', '150.00')
            ->assertViewHas('expenseTotal', '40.00')
            ->assertViewHas('balance', '110.00')
            ->assertSeeText('Ingreso incluido uno')
            ->assertSeeText('Ingreso incluido dos')
            ->assertSeeText('Gasto incluido')
            ->assertDontSeeText('Ingreso excluido')
            ->assertDontSeeText('Gasto excluido');
    }

    #[DataProvider('authorizedRoleProvider')]
    public function test_administrator_and_finance_roles_can_access_finance_pages(string $roleName): void
    {
        $user = $this->userWithRole($roleName);

        $this->actingAs($user)->get(route('finanzas.index'))->assertOk();
        $this->actingAs($user)->get(route('finanzas.ingresos.index'))->assertOk();
        $this->actingAs($user)->get(route('finanzas.gastos.index'))->assertOk();
    }

    public static function authorizedRoleProvider(): array
    {
        return [
            'administrator' => [Role::ADMINISTRATOR],
            'finance' => [Role::FINANCE],
        ];
    }

    public function test_sales_role_cannot_access_or_create_finance_records(): void
    {
        $user = $this->userWithRole(Role::SALES);

        $this->actingAs($user)->get(route('finanzas.index'))->assertForbidden();
        $this->actingAs($user)
            ->post(route('finanzas.ingresos.store'), [
                'date' => '2026-09-22',
                'amount' => '10.00',
                'description' => 'Ingreso no autorizado',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('incomes', 0);
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::factory()->create([
            'name' => $roleName,
        ]);

        return User::factory()->for($role)->create([
            'is_active' => true,
        ]);
    }
}
