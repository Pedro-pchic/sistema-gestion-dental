<?php

namespace App\Http\Controllers;

use App\Models\Dentist;
use App\Models\Role;
use App\Services\DentalDashboardService;
use App\Services\ManagementDashboardService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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

            $isAdministrator = $user->hasRole(Role::ADMINISTRATOR);
            if ($request->has('dentist_id') && ! $isAdministrator) {
                abort(403);
            }

            $filters = $request->validate([
                'from' => ['nullable', 'date_format:Y-m-d'],
                'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
                'period' => ['nullable', Rule::in(['today', 'last_7_days', 'this_month', 'previous_month', 'custom'])],
                'dentist_id' => ['nullable', 'integer', Rule::exists('dentists', 'id')],
            ]);
            $today = CarbonImmutable::today(config('appointments.timezone'));
            $period = $filters['period'] ?? (isset($filters['from']) || isset($filters['to']) ? 'custom' : 'today');
            [$from, $to] = match ($period) {
                'last_7_days' => [$today->subDays(6)->toDateString(), $today->toDateString()],
                'this_month' => [$today->startOfMonth()->toDateString(), $today->toDateString()],
                'previous_month' => [$today->subMonth()->startOfMonth()->toDateString(), $today->startOfMonth()->subDay()->toDateString()],
                default => [$filters['from'] ?? $filters['to'] ?? $today->toDateString(), $filters['to'] ?? $filters['from'] ?? $today->toDateString()],
            };

            if (CarbonImmutable::parse($from)->diffInDays(CarbonImmutable::parse($to)) > 30) {
                throw ValidationException::withMessages(['to' => 'El período no puede superar 31 días.']);
            }

            $dentistId = $isAdministrator && isset($filters['dentist_id']) ? (int) $filters['dentist_id'] : null;

            return view('dashboard.dental', [
                'dashboardMetrics' => $dentalDashboard->dashboard($user, $from, $to, $dentistId),
                'from' => $from,
                'to' => $to,
                'period' => $period,
                'dentistId' => $dentistId,
                'dentists' => $isAdministrator ? Dentist::query()->with('user:id,name')->orderBy('id')->get(['id', 'user_id']) : collect(),
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
