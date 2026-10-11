<?php

namespace App\Http\Controllers;

use App\Enums\FinancialCategory;
use App\Enums\OrderStatus;
use App\Enums\PurchaseStatus;
use App\Enums\SaleStatus;
use App\Models\Order;
use App\Models\Purchase;
use App\Models\Sale;
use App\Services\ManagementDashboardService;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ManagementReportController extends Controller
{
    public function index(Request $request, ManagementDashboardService $managementDashboard): View
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'sale_status' => ['nullable', Rule::in(array_map(
                fn (SaleStatus $status): string => $status->value,
                SaleStatus::cases(),
            ))],
            'purchase_status' => ['nullable', Rule::in(array_map(
                fn (PurchaseStatus $status): string => $status->value,
                PurchaseStatus::cases(),
            ))],
            'logistics_status' => ['nullable', Rule::in(array_map(
                fn (OrderStatus $status): string => $status->value,
                OrderStatus::logisticsStages(),
            ))],
        ]);
        $from = $filters['from'] ?? today()->startOfMonth()->toDateString();
        $to = $filters['to'] ?? today()->toDateString();

        $sales = Sale::query()
            ->whereBetween('sale_date', [$from, $to])
            ->when(isset($filters['sale_status']), fn ($query) => $query->where('status', $filters['sale_status']))
            ->orderByDesc('sale_date')
            ->orderByDesc('id')
            ->limit(20)
            ->get(['id', 'number', 'sale_date', 'status', 'total']);

        $purchasesQuery = Purchase::query()
            ->whereBetween('order_date', [$from, $to])
            ->when(isset($filters['purchase_status']), fn ($query) => $query->where('status', $filters['purchase_status']))
            ->orderByDesc('order_date')
            ->orderByDesc('id');
        $purchases = (clone $purchasesQuery)
            ->limit(20)
            ->get(['id', 'number', 'order_date', 'status', 'total']);

        $logisticsOrdersQuery = Order::query()
            ->eligibleForLogistics()
            ->whereBetween('order_date', [$from, $to])
            ->when(isset($filters['logistics_status']), function ($query) use ($filters): void {
                if ($filters['logistics_status'] === OrderStatus::Confirmed->value) {
                    $query->whereNull('logistics_status');
                } else {
                    $query->where('logistics_status', $filters['logistics_status']);
                }
            })
            ->with('sale:id,order_id,status')
            ->orderByDesc('order_date')
            ->orderByDesc('id');
        $logisticsOrders = (clone $logisticsOrdersQuery)
            ->limit(20)
            ->get([
                'id',
                'number',
                'status',
                'order_date',
                'logistics_status',
                'estimated_delivery_at',
                'dispatched_at',
                'delivered_at',
            ]);

        $canViewFinance = $request->user()?->can('manage-finances') ?? false;
        $financialSummary = null;
        $incomeCategories = collect();
        $expenseCategories = collect();

        if ($canViewFinance) {
            $financialSummary = $managementDashboard->financialSummary($from, $to);
            $incomeCategories = $this->financialCategories('incomes', $from, $to);
            $expenseCategories = $this->financialCategories('expenses', $from, $to);
        }

        return view('management.reports.index', [
            'canViewFinance' => $canViewFinance,
            'expenseCategories' => $expenseCategories,
            'financialSummary' => $financialSummary,
            'from' => $from,
            'incomeCategories' => $incomeCategories,
            'inventorySummary' => $managementDashboard->inventorySummary(),
            'logisticsOrders' => $logisticsOrders,
            'logisticsStatuses' => OrderStatus::logisticsStages(),
            'lowStockProducts' => $managementDashboard->lowStockProducts(50),
            'purchases' => $purchases,
            'purchasesSummary' => $managementDashboard->purchasesSummary($from, $to),
            'purchaseStatuses' => PurchaseStatus::cases(),
            'sales' => $sales,
            'salesSummary' => $managementDashboard->salesSummary($from, $to),
            'saleStatuses' => SaleStatus::cases(),
            'to' => $to,
        ]);
    }

    /**
     * @return Collection<int, array{category: string, total: string}>
     */
    private function financialCategories(string $table, string $from, string $to): Collection
    {
        return DB::table($table)
            ->whereBetween('date', [$from, $to])
            ->select('category')
            ->selectRaw('SUM(amount) AS total')
            ->groupBy('category')
            ->orderBy('category')
            ->get()
            ->map(fn (object $row): array => [
                'category' => FinancialCategory::tryFrom($row->category)?->label() ?? 'Otra',
                'total' => Money::fromCents(Money::toCents((string) $row->total)),
            ]);
    }
}
