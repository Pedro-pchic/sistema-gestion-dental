<?php

namespace App\Http\Controllers;

use App\Enums\EcommerceEventType;
use App\Models\EcommerceEvent;
use App\Services\ManagementDashboardService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketingAnalyticsController extends Controller
{
    public function index(Request $request, ManagementDashboardService $managementDashboard): View
    {
        $request->merge([
            'date_from' => $request->input('date_from', now()->startOfMonth()->toDateString()),
            'date_to' => $request->input('date_to', now()->toDateString()),
        ]);

        $filters = $request->validate([
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
        ]);

        $dateFrom = CarbonImmutable::parse($filters['date_from'])->startOfDay();
        $dateTo = CarbonImmutable::parse($filters['date_to'])->endOfDay();

        $metrics = $managementDashboard->marketingMetrics($dateFrom, $dateTo);

        $topViewedProducts = EcommerceEvent::query()
            ->select('product_id')
            ->selectRaw('COUNT(*) AS total_views')
            ->where('event_type', EcommerceEventType::ProductViewed->value)
            ->whereNotNull('product_id')
            ->whereBetween('occurred_at', [$dateFrom, $dateTo])
            ->with('product')
            ->groupBy('product_id')
            ->orderByDesc('total_views')
            ->orderBy('product_id')
            ->limit(5)
            ->get();

        $topSellingProducts = $managementDashboard->topSellingProducts(
            $filters['date_from'],
            $filters['date_to'],
        );

        return view('marketing.analytics.index', compact(
            'dateFrom',
            'dateTo',
            'metrics',
            'topSellingProducts',
            'topViewedProducts',
        ));
    }
}
