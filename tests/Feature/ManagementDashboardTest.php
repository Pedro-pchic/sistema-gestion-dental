<?php

namespace Tests\Feature;

use App\Enums\EcommerceEventType;
use App\Enums\FinancialCategory;
use App\Enums\OrderStatus;
use App\Enums\PurchaseStatus;
use App\Enums\SaleStatus;
use App\Models\Department;
use App\Models\EcommerceEvent;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Position;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ManagementDashboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_manager_can_view_filtered_management_dashboard_using_existing_module_data(): void
    {
        $manager = $this->userWithRole(Role::MANAGER);
        $customerSale = Sale::factory()->create([
            'sale_date' => '2026-06-12',
            'status' => SaleStatus::Confirmed,
            'total' => '100.50',
        ]);
        Sale::factory()->create([
            'sale_date' => '2026-06-12',
            'status' => SaleStatus::Cancelled,
            'total' => '900.00',
        ]);
        Sale::factory()->create([
            'sale_date' => '2026-05-31',
            'status' => SaleStatus::Confirmed,
            'total' => '50.00',
        ]);

        $topProduct = Product::factory()->create(['name' => 'Bota gerencial']);
        SaleDetail::factory()->for($customerSale)->for($topProduct)->create(['quantity' => 3]);
        $lowProduct = Product::factory()->create(['name' => 'Talla con pocas existencias']);
        Inventory::factory()->for($lowProduct)->create(['stock' => 2, 'minimum_stock' => 3]);
        $emptyProduct = Product::factory()->create(['name' => 'Agotado']);
        Inventory::factory()->for($emptyProduct)->create(['stock' => 0, 'minimum_stock' => 0]);

        Income::factory()->create([
            'date' => '2026-06-10',
            'amount' => '250.25',
            'category' => FinancialCategory::Sales,
        ]);
        Expense::factory()->create([
            'date' => '2026-06-11',
            'amount' => '40.10',
            'category' => FinancialCategory::Operating,
        ]);
        Income::factory()->create(['date' => '2026-05-31', 'amount' => '800.00']);

        Purchase::factory()->create([
            'status' => PurchaseStatus::Pending,
            'order_date' => '2026-06-09',
            'total' => '300.00',
        ]);
        Purchase::factory()->create([
            'status' => PurchaseStatus::Cancelled,
            'order_date' => '2026-06-09',
            'total' => '500.00',
        ]);

        EcommerceEvent::factory()->create([
            'event_type' => EcommerceEventType::PortalVisit,
            'session_identifier' => str_repeat('a', 64),
            'occurred_at' => '2026-06-12 10:00:00',
        ]);
        EcommerceEvent::factory()->create([
            'event_type' => EcommerceEventType::CartStarted,
            'occurred_at' => '2026-06-12 11:00:00',
        ]);
        EcommerceEvent::factory()->create([
            'event_type' => EcommerceEventType::CheckoutStarted,
            'occurred_at' => '2026-06-12 11:30:00',
        ]);

        $department = Department::factory()->create(['name' => 'Ventas']);
        $position = Position::factory()->for($department)->create();
        Employee::factory()->create(['position_id' => $position->id, 'is_active' => true]);
        Employee::factory()->create(['position_id' => $position->id, 'is_active' => false]);

        $deliveredOrder = Order::factory()->create([
            'status' => OrderStatus::Completed,
            'logistics_status' => OrderStatus::Delivered,
            'order_date' => '2026-06-12',
            'dispatched_at' => '2026-06-10 10:00:00',
            'delivered_at' => '2026-06-10 16:00:00',
        ]);
        Sale::factory()->create([
            'order_id' => $deliveredOrder->id,
            'sale_date' => '2026-06-12',
            'status' => SaleStatus::Confirmed,
            'total' => '80.00',
        ]);

        $lateOrder = Order::factory()->create([
            'status' => OrderStatus::Completed,
            'logistics_status' => OrderStatus::InTransit,
            'order_date' => '2026-06-13',
            'estimated_delivery_at' => '2026-06-14 10:00:00',
        ]);
        Sale::factory()->create([
            'order_id' => $lateOrder->id,
            'sale_date' => '2026-06-13',
            'status' => SaleStatus::Confirmed,
        ]);

        $response = $this->actingAs($manager)
            ->get(route('dashboard', ['from' => '2026-06-01', 'to' => '2026-06-30']))
            ->assertOk()
            ->assertViewHas('isManagementDashboard', true);
        $metrics = $response->viewData('dashboardMetrics');

        $this->assertSame(3, $metrics['sales']['count']);
        $this->assertSame('180.50', $metrics['sales']['total']);
        $this->assertSame('60.17', $metrics['sales']['average_ticket']);
        $this->assertSame([
            'income' => '250.25',
            'expense' => '40.10',
            'balance' => '210.15',
        ], $metrics['finance']);
        $this->assertSame(1, $metrics['inventory']['low_stock']);
        $this->assertSame(1, $metrics['inventory']['out_of_stock']);
        $this->assertSame(2, $metrics['purchases']['count']);
        $this->assertSame('300.00', $metrics['purchases']['active_total']);
        $this->assertSame(1, $metrics['marketing']['visits']);
        $this->assertSame(1, $metrics['marketing']['carts_started']);
        $this->assertEqualsWithDelta(0.0, $metrics['marketing']['conversion_rate'], 0.01);
        $this->assertEqualsWithDelta(100.0, $metrics['marketing']['abandonment_rate'], 0.01);
        $this->assertSame(1, $metrics['logistics']['stages'][OrderStatus::Delivered->value]);
        $this->assertSame(1, $metrics['logistics']['delayed']);
        $this->assertEqualsWithDelta(6.0, $metrics['logistics']['average_delivery_hours'], 0.01);
        $this->assertSame(1, $metrics['human_resources']['active_employees']);
        $this->assertSame(1, $metrics['human_resources']['occupied_positions']);
        $this->assertSame(1, (int) $metrics['human_resources']['employees_by_department']->first()->active_employees);
        $this->assertSame($topProduct->id, $metrics['top_selling_products'][0]->product_id);

        $response
            ->assertSeeText('Control de Gestión')
            ->assertSeeText('Bota gerencial')
            ->assertSeeText('Tiempo promedio de entrega');
    }

    public function test_admin_can_view_management_dashboard_but_other_roles_keep_basic_dashboard(): void
    {
        $admin = $this->userWithRole(Role::ADMINISTRATOR);
        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('isManagementDashboard', true);

        $salesUser = $this->userWithRole(Role::SALES);
        $this->actingAs($salesUser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('isManagementDashboard', false)
            ->assertDontSeeText('Ingresos');

        $this->actingAs($salesUser)
            ->get(route('gestion.reportes.index'))
            ->assertForbidden();
    }

    public function test_manager_dashboard_returns_correct_order_status_counts_for_cancelled_completed_and_logistics_orders(): void
    {
        $manager = $this->userWithRole(Role::MANAGER);

        $cancelledOrder = Order::factory()->create([
            'status' => OrderStatus::Cancelled,
            'order_date' => '2026-06-10',
        ]);
        Sale::factory()->create([
            'order_id' => $cancelledOrder->id,
            'sale_date' => '2026-06-10',
            'status' => SaleStatus::Confirmed,
        ]);

        $cancelledSaleOrder = Order::factory()->create([
            'status' => OrderStatus::Completed,
            'logistics_status' => OrderStatus::Delivered,
            'order_date' => '2026-06-11',
        ]);
        Sale::factory()->create([
            'order_id' => $cancelledSaleOrder->id,
            'sale_date' => '2026-06-11',
            'status' => SaleStatus::Cancelled,
        ]);

        Order::factory()->create([
            'status' => OrderStatus::Completed,
            'logistics_status' => OrderStatus::InTransit,
            'order_date' => '2026-06-12',
        ]);
        Order::factory()->create([
            'status' => OrderStatus::Completed,
            'logistics_status' => null,
            'order_date' => '2026-06-13',
        ]);

        $response = $this->actingAs($manager)
            ->get(route('dashboard', ['from' => '2026-06-01', 'to' => '2026-06-30']))
            ->assertOk();

        $orderStatuses = $response->viewData('dashboardMetrics')['order_statuses'];

        $this->assertSame(2, $orderStatuses[OrderStatus::Cancelled->value]);
        $this->assertSame(1, $orderStatuses[OrderStatus::InTransit->value]);
        $this->assertSame(1, $orderStatuses[OrderStatus::Confirmed->value]);
    }

    public function test_manager_dashboard_handles_empty_period_and_rejects_an_invalid_range(): void
    {
        $manager = $this->userWithRole(Role::MANAGER);

        $this->actingAs($manager)
            ->get(route('dashboard', ['from' => '2026-06-01', 'to' => '2026-06-30']))
            ->assertOk()
            ->assertViewHas('dashboardMetrics', function (array $metrics): bool {
                return $metrics['sales']['count'] === 0
                    && $metrics['sales']['average_ticket'] === '0.00'
                    && $metrics['finance']['balance'] === '0.00'
                    && $metrics['marketing']['conversion_rate'] === 0.0
                    && $metrics['marketing']['abandonment_rate'] === 0.0
                    && $metrics['logistics']['average_delivery_hours'] === null;
            });

        $this->actingAs($manager)
            ->get(route('dashboard', ['from' => '2026-06-30', 'to' => '2026-06-01']))
            ->assertSessionHasErrors('to');
    }

    private function userWithRole(string $roleName): User
    {
        return User::factory()
            ->for(Role::factory()->create(['name' => $roleName]))
            ->create(['is_active' => true]);
    }
}
