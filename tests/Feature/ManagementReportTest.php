<?php

namespace Tests\Feature;

use App\Enums\FinancialCategory;
use App\Enums\OrderStatus;
use App\Enums\PurchaseStatus;
use App\Enums\SaleStatus;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ManagementReportTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_manager_can_filter_operational_reports_by_period_and_status(): void
    {
        $manager = $this->userWithRole(Role::MANAGER);
        Sale::factory()->create([
            'number' => 'VEN-REPORT-CANCELLED',
            'sale_date' => '2026-06-10',
            'status' => SaleStatus::Cancelled,
            'total' => '150.00',
        ]);
        Sale::factory()->create([
            'number' => 'VEN-REPORT-OUTSIDE',
            'sale_date' => '2026-05-10',
            'status' => SaleStatus::Cancelled,
        ]);
        Purchase::factory()->create([
            'number' => 'OC-REPORT-PENDING',
            'order_date' => '2026-06-11',
            'status' => PurchaseStatus::Pending,
            'total' => '90.00',
        ]);
        Purchase::factory()->create([
            'number' => 'OC-REPORT-RECEIVED',
            'order_date' => '2026-06-11',
            'status' => PurchaseStatus::Received,
        ]);

        $confirmedLogisticsOrder = Order::factory()->create([
            'number' => 'PED-REPORT-CONFIRMED',
            'status' => OrderStatus::Completed,
            'logistics_status' => null,
            'order_date' => '2026-06-12',
        ]);
        Sale::factory()->create([
            'order_id' => $confirmedLogisticsOrder->id,
            'status' => SaleStatus::Confirmed,
            'sale_date' => '2026-06-12',
        ]);
        $inTransitOrder = Order::factory()->create([
            'number' => 'PED-REPORT-TRANSIT',
            'status' => OrderStatus::Completed,
            'logistics_status' => OrderStatus::InTransit,
            'order_date' => '2026-06-12',
        ]);
        Sale::factory()->create([
            'order_id' => $inTransitOrder->id,
            'status' => SaleStatus::Confirmed,
            'sale_date' => '2026-06-12',
        ]);

        $lowProduct = Product::factory()->create(['name' => 'Inventario en alerta']);
        Inventory::factory()->for($lowProduct)->create(['stock' => 1, 'minimum_stock' => 2]);

        $this->actingAs($manager)
            ->get(route('gestion.reportes.index', [
                'from' => '2026-06-01',
                'to' => '2026-06-30',
                'sale_status' => SaleStatus::Cancelled->value,
                'purchase_status' => PurchaseStatus::Pending->value,
                'logistics_status' => OrderStatus::Confirmed->value,
            ]))
            ->assertOk()
            ->assertViewHas('canViewFinance', false)
            ->assertSeeText('VEN-REPORT-CANCELLED')
            ->assertDontSeeText('VEN-REPORT-OUTSIDE')
            ->assertSeeText('OC-REPORT-PENDING')
            ->assertDontSeeText('OC-REPORT-RECEIVED')
            ->assertSeeText('PED-REPORT-CONFIRMED')
            ->assertDontSeeText('PED-REPORT-TRANSIT')
            ->assertSeeText('Inventario en alerta')
            ->assertDontSeeText('Finanzas por categoría');
    }

    public function test_sensitive_finance_report_is_only_loaded_for_users_with_finance_permission(): void
    {
        Income::factory()->create([
            'date' => '2026-06-10',
            'amount' => '125.50',
            'category' => FinancialCategory::OtherIncome,
        ]);
        Expense::factory()->create([
            'date' => '2026-06-11',
            'amount' => '25.25',
            'category' => FinancialCategory::Operating,
        ]);
        $manager = $this->userWithRole(Role::MANAGER);

        $this->actingAs($manager)
            ->get(route('gestion.reportes.index', ['from' => '2026-06-01', 'to' => '2026-06-30']))
            ->assertOk()
            ->assertViewHas('canViewFinance', false)
            ->assertDontSeeText('Ingresos por categoría')
            ->assertDontSeeText('Operativos');

        $admin = $this->userWithRole(Role::ADMINISTRATOR);
        $this->actingAs($admin)
            ->get(route('gestion.reportes.index', ['from' => '2026-06-01', 'to' => '2026-06-30']))
            ->assertOk()
            ->assertViewHas('canViewFinance', true)
            ->assertSeeText('Ingresos por categoría')
            ->assertSeeText('Otros ingresos')
            ->assertSeeText('Operativos')
            ->assertSeeText('125.50')
            ->assertSeeText('25.25');
    }

    public function test_report_date_range_is_validated_and_users_without_management_access_are_denied(): void
    {
        $salesUser = $this->userWithRole(Role::SALES);
        $this->actingAs($salesUser)
            ->get(route('gestion.reportes.index'))
            ->assertForbidden();

        $manager = $this->userWithRole(Role::MANAGER);
        $this->actingAs($manager)
            ->get(route('gestion.reportes.index', ['from' => '2026-06-30', 'to' => '2026-06-01']))
            ->assertSessionHasErrors('to');
    }

    private function userWithRole(string $roleName): User
    {
        return User::factory()
            ->for(Role::factory()->create(['name' => $roleName]))
            ->create(['is_active' => true]);
    }
}
