<?php

namespace App\Services;

use App\Enums\EcommerceEventType;
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
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ManagementDashboardService
{
    /**
     * @return array{income: string, expense: string, balance: string}
     */
    public function financialSummary(string $from, string $to): array
    {
        $incomeSummary = Income::query()
            ->whereBetween('date', [$from, $to])
            ->selectRaw('COALESCE(SUM(amount), 0) AS total');
        $expenseSummary = Expense::query()
            ->whereBetween('date', [$from, $to])
            ->selectRaw('COALESCE(SUM(amount), 0) AS total');
        $summary = DB::query()
            ->fromSub($incomeSummary, 'income_summary')
            ->crossJoinSub($expenseSummary, 'expense_summary')
            ->selectRaw('income_summary.total AS income_total')
            ->selectRaw('expense_summary.total AS expense_total')
            ->first();
        $incomeCents = Money::toCents((string) $summary->income_total);
        $expenseCents = Money::toCents((string) $summary->expense_total);

        return [
            'income' => Money::fromCents($incomeCents),
            'expense' => Money::fromCents($expenseCents),
            'balance' => Money::fromCents($incomeCents - $expenseCents),
        ];
    }

    /**
     * @return array{count: int, total: string, average_ticket: string, active_customers: int}
     */
    public function salesSummary(string $from, string $to): array
    {
        $summary = Sale::query()
            ->confirmed()
            ->whereBetween('sale_date', [$from, $to])
            ->selectRaw('COUNT(*) AS sales_count')
            ->selectRaw('COALESCE(SUM(total), 0) AS sales_total')
            ->selectRaw('ROUND(COALESCE(AVG(total), 0), 2) AS average_ticket')
            ->selectRaw('COUNT(DISTINCT customer_id) AS active_customers')
            ->first();

        $count = (int) $summary->sales_count;
        $totalCents = Money::toCents((string) $summary->sales_total);

        return [
            'count' => $count,
            'total' => Money::fromCents($totalCents),
            'average_ticket' => Money::fromCents(Money::toCents((string) $summary->average_ticket)),
            'active_customers' => (int) $summary->active_customers,
        ];
    }

    /**
     * @return Collection<int, SaleDetail>
     */
    public function topSellingProducts(string $from, string $to, int $limit = 5): Collection
    {
        return SaleDetail::query()
            ->select('product_id')
            ->selectRaw('SUM(sale_details.quantity) AS units_sold')
            ->whereHas('sale', function (Builder $query) use ($from, $to): void {
                $query->confirmed()->whereBetween('sale_date', [$from, $to]);
            })
            ->with('product')
            ->groupBy('product_id')
            ->orderByDesc('units_sold')
            ->orderBy('product_id')
            ->limit($limit)
            ->get();
    }

    /**
     * @return array{count: int, active_total: string, statuses: array<string, int>}
     */
    public function purchasesSummary(string $from, string $to): array
    {
        $summary = Purchase::query()
            ->whereBetween('order_date', [$from, $to])
            ->selectRaw('COUNT(*) AS purchase_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN status IN (?, ?) THEN total ELSE 0 END), 0) AS active_total', [
                PurchaseStatus::Pending->value,
                PurchaseStatus::Received->value,
            ])
            ->selectRaw('COUNT(CASE WHEN status = ? THEN 1 END) AS draft_count', [PurchaseStatus::Draft->value])
            ->selectRaw('COUNT(CASE WHEN status = ? THEN 1 END) AS pending_count', [PurchaseStatus::Pending->value])
            ->selectRaw('COUNT(CASE WHEN status = ? THEN 1 END) AS received_count', [PurchaseStatus::Received->value])
            ->selectRaw('COUNT(CASE WHEN status = ? THEN 1 END) AS cancelled_count', [PurchaseStatus::Cancelled->value])
            ->first();

        return [
            'count' => (int) $summary->purchase_count,
            'active_total' => Money::fromCents(Money::toCents((string) $summary->active_total)),
            'statuses' => [
                PurchaseStatus::Draft->value => (int) $summary->draft_count,
                PurchaseStatus::Pending->value => (int) $summary->pending_count,
                PurchaseStatus::Received->value => (int) $summary->received_count,
                PurchaseStatus::Cancelled->value => (int) $summary->cancelled_count,
            ],
        ];
    }

    /** @return array{low_stock: int, out_of_stock: int} */
    public function inventorySummary(): array
    {
        $summary = Inventory::query()
            ->selectRaw('SUM(CASE WHEN stock > 0 AND minimum_stock > 0 AND stock <= minimum_stock THEN 1 ELSE 0 END) AS low_stock')
            ->selectRaw('SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END) AS out_of_stock')
            ->first();

        return [
            'low_stock' => (int) ($summary->low_stock ?? 0),
            'out_of_stock' => (int) ($summary->out_of_stock ?? 0),
        ];
    }

    /**
     * @return Collection<int, Inventory>
     */
    public function lowStockProducts(int $limit = 10): Collection
    {
        return Inventory::query()
            ->with('product:id,name,sku')
            ->where(function (Builder $query): void {
                $query->where('stock', 0)
                    ->orWhere(function (Builder $lowStockQuery): void {
                        $lowStockQuery
                            ->where('stock', '>', 0)
                            ->where('minimum_stock', '>', 0)
                            ->whereColumn('stock', '<=', 'minimum_stock');
                    });
            })
            ->orderBy('stock')
            ->orderBy('product_id')
            ->limit($limit)
            ->get(['product_id', 'stock', 'minimum_stock']);
    }

    /**
     * @return array{visits: int, products_viewed: int, carts_started: int, checkouts_started: int, orders_completed: int, conversion_rate: float, abandonment_rate: float}
     */
    public function marketingMetrics(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $totals = EcommerceEvent::query()
            ->whereBetween('occurred_at', [$from, $to])
            ->selectRaw(
                'COUNT(DISTINCT CASE WHEN event_type = ? THEN session_identifier END) AS visits',
                [EcommerceEventType::PortalVisit->value],
            )
            ->selectRaw(
                'COUNT(CASE WHEN event_type = ? THEN id END) AS products_viewed',
                [EcommerceEventType::ProductViewed->value],
            )
            ->selectRaw(
                'COUNT(CASE WHEN event_type = ? THEN id END) AS carts_started',
                [EcommerceEventType::CartStarted->value],
            )
            ->selectRaw(
                'COUNT(CASE WHEN event_type = ? THEN id END) AS checkouts_started',
                [EcommerceEventType::CheckoutStarted->value],
            )
            ->selectRaw(
                'COUNT(DISTINCT CASE WHEN event_type = ? THEN order_id END) AS orders_completed',
                [EcommerceEventType::OrderCompleted->value],
            )
            ->first();

        $visits = (int) $totals->visits;
        $cartsStarted = (int) $totals->carts_started;
        $ordersCompleted = (int) $totals->orders_completed;

        return [
            'visits' => $visits,
            'products_viewed' => (int) $totals->products_viewed,
            'carts_started' => $cartsStarted,
            'checkouts_started' => (int) $totals->checkouts_started,
            'orders_completed' => $ordersCompleted,
            'conversion_rate' => $visits > 0 ? ($ordersCompleted / $visits) * 100 : 0.0,
            'abandonment_rate' => $cartsStarted > 0
                ? (max(0, $cartsStarted - $ordersCompleted) / $cartsStarted) * 100
                : 0.0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboard(string $from, string $to): array
    {
        $sales = $this->salesSummary($from, $to);
        $finance = $this->financialSummary($from, $to);
        $purchases = $this->purchasesSummary($from, $to);
        $inventorySummary = $this->inventorySummary();
        $lowStockProducts = $this->lowStockProducts();

        $orderStatusExpression = 'CASE WHEN orders.status = ? OR sales.status = ? THEN ? WHEN orders.logistics_status IS NOT NULL THEN orders.logistics_status WHEN orders.status = ? THEN ? ELSE orders.status END';
        $orderStatusBindings = [
            OrderStatus::Cancelled->value,
            SaleStatus::Cancelled->value,
            OrderStatus::Cancelled->value,
            OrderStatus::Completed->value,
            OrderStatus::Confirmed->value,
        ];
        $ordersWithStatus = Order::query()
            ->leftJoin('sales', 'sales.order_id', '=', 'orders.id')
            ->whereBetween('orders.order_date', [$from, $to])
            ->select('orders.id as order_id')
            ->selectRaw($orderStatusExpression.' AS current_status', $orderStatusBindings);

        $orderStatuses = DB::query()
            ->fromSub($ordersWithStatus, 'orders_with_status')
            ->select('orders_with_status.current_status')
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('orders_with_status.current_status')
            ->get()
            ->mapWithKeys(fn (object $row): array => [$row->current_status => (int) $row->total]);

        $eligibleLogisticsOrders = Order::query()
            ->eligibleForLogistics()
            ->whereBetween('order_date', [$from, $to]);
        $logisticsStages = (clone $eligibleLogisticsOrders)
            ->selectRaw("COALESCE(logistics_status, 'confirmed') AS current_status")
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('current_status')
            ->get()
            ->mapWithKeys(fn (object $row): array => [$row->current_status => (int) $row->total]);

        $averageDeliveryHours = $this->averageDeliveryHours(clone $eligibleLogisticsOrders);
        $humanResourcesSummary = Employee::query()
            ->selectRaw('COUNT(CASE WHEN is_active THEN 1 END) AS active_employees')
            ->selectRaw('COUNT(DISTINCT CASE WHEN is_active AND position_id IS NOT NULL THEN position_id END) AS occupied_positions')
            ->first();
        $activeEmployeesByDepartment = Department::query()
            ->leftJoin('positions', 'positions.department_id', '=', 'departments.id')
            ->leftJoin('employees', function ($join): void {
                $join->on('employees.position_id', '=', 'positions.id')
                    ->where('employees.is_active', true);
            })
            ->select('departments.id', 'departments.name')
            ->selectRaw('COUNT(DISTINCT employees.id) AS active_employees')
            ->groupBy('departments.id', 'departments.name')
            ->orderBy('departments.name')
            ->get();

        return [
            'finance' => $finance,
            'sales' => $sales,
            'order_statuses' => $this->statusCounts($orderStatuses, OrderStatus::cases()),
            'top_selling_products' => $this->topSellingProducts($from, $to),
            'inventory' => [
                'low_stock' => $inventorySummary['low_stock'],
                'out_of_stock' => $inventorySummary['out_of_stock'],
                'low_stock_products' => $lowStockProducts,
            ],
            'purchases' => [
                ...$purchases,
            ],
            'marketing' => $this->marketingMetrics(
                CarbonImmutable::parse($from)->startOfDay(),
                CarbonImmutable::parse($to)->endOfDay(),
            ),
            'logistics' => [
                'stages' => $this->statusCounts($logisticsStages, OrderStatus::logisticsStages()),
                'delayed' => (clone $eligibleLogisticsOrders)->delayed()->count(),
                'average_delivery_hours' => $averageDeliveryHours,
            ],
            'human_resources' => [
                'active_employees' => (int) $humanResourcesSummary->active_employees,
                'occupied_positions' => (int) $humanResourcesSummary->occupied_positions,
                'employees_by_department' => $activeEmployeesByDepartment,
            ],
        ];
    }

    /**
     * @param  Collection<int, object>  $counts
     * @param  array<int, \BackedEnum>  $statuses
     * @return array<string, int>
     */
    private function statusCounts(Collection $counts, array $statuses): array
    {
        $values = $counts->all();
        $totals = [];

        foreach ($statuses as $status) {
            $totals[$status->value] = (int) ($values[$status->value] ?? 0);
        }

        return $totals;
    }

    private function averageDeliveryHours(Builder $orders): ?float
    {
        $driver = DB::connection()->getDriverName();
        $expression = match ($driver) {
            'pgsql' => 'EXTRACT(EPOCH FROM (delivered_at - dispatched_at)) / 3600.0',
            'sqlite' => '(julianday(delivered_at) - julianday(dispatched_at)) * 24.0',
            'mysql' => 'TIMESTAMPDIFF(SECOND, dispatched_at, delivered_at) / 3600.0',
            default => null,
        };

        if ($expression === null) {
            return null;
        }

        $average = $orders
            ->whereNotNull('dispatched_at')
            ->whereNotNull('delivered_at')
            ->whereColumn('delivered_at', '>=', 'dispatched_at')
            ->selectRaw("AVG({$expression}) AS average_hours")
            ->value('average_hours');

        return $average === null ? null : (float) $average;
    }
}
