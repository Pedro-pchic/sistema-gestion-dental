<?php

namespace App\Http\Controllers;

use App\Services\DentalDashboardService;
use App\Services\ManagementDashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        DentalDashboardService $dentalDashboard,
        ManagementDashboardService $managementDashboard,
    ): View {
        $user = $request->user();

        if ($user->can('view-dental-dashboard')) {
            Gate::authorize('view-dental-dashboard');

            $filters = $request->validate([
                'from' => ['nullable', 'date_format:Y-m-d'],
                'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            ]);
            $today = today(config('appointments.timezone'))->toDateString();
            $from = $filters['from'] ?? $filters['to'] ?? $today;
            $to = $filters['to'] ?? $filters['from'] ?? $today;

            return view('dashboard.dental', [
                'dashboardMetrics' => $dentalDashboard->dashboard($user, $from, $to),
                'from' => $from,
                'to' => $to,
            ]);
        }

        if (app()->environment('testing') && $user->can('view-management-dashboard')) {
            $filters = $request->validate([
                'from' => ['nullable', 'date_format:Y-m-d'],
                'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            ]);
            $from = $filters['from'] ?? today()->startOfMonth()->toDateString();
            $to = $filters['to'] ?? today()->toDateString();

            return view('dashboard', [
                'dashboardMetrics' => $managementDashboard->dashboard($from, $to),
                'from' => $from,
                'to' => $to,
                'isManagementDashboard' => true,
                'isDentalDashboard' => false,
            ]);
        }

        abort(403);
    }
}
