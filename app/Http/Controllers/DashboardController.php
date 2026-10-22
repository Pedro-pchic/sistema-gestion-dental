<?php

namespace App\Http\Controllers;

use App\Models\Dentist;
use App\Models\Role;
use App\Services\DentalDashboardService;
use App\Services\ManagementDashboardService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        DentalDashboardService $dentalDashboard,
        ManagementDashboardService $managementDashboard,
    ): View|Response {
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

            return Inertia::render('Dashboard/Index', [
                'dashboardMetrics' => $this->inertiaMetrics($dentalDashboard->dashboard($user, $from, $to, $dentistId), config('appointments.timezone')),
                'from' => $from,
                'to' => $to,
                'period' => $period,
                'dentistId' => $dentistId,
                'dentists' => $isAdministrator ? Dentist::query()->with('user:id,name')->orderBy('id')->get(['id', 'user_id'])->map(fn (Dentist $dentist): array => [
                    'id' => $dentist->id,
                    'name' => $dentist->user?->name ?? 'Odontólogo #'.$dentist->id,
                ]) : [],
                'urls' => [
                    'dashboard' => route('dashboard'),
                    'appointments' => route('citas.index'),
                    'patients' => route('pacientes.index'),
                    'invoices' => $user->can('view-dental-finances') ? route('facturacion.facturas.index') : null,
                    'payments' => $user->can('view-dental-finances') ? route('facturacion.pagos.index') : null,
                    'reports' => $user->can('view-dental-reports') ? route('facturacion.reportes.index', ['from' => $from, 'to' => $to]) : null,
                    'reminders' => $user->can('view-appointment-reminders') ? route('recordatorios.index') : null,
                ],
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

    /** @param array<string, mixed> $metrics
     * @return array<string, mixed>
     */
    private function inertiaMetrics(array $metrics, string $timezone): array
    {
        $data = Arr::only($metrics, [
            'role', 'active_patients', 'registered_patients', 'appointments_today',
            'scheduled_appointments', 'confirmed_appointments', 'cancelled_appointments',
            'completed_appointments', 'pending_appointments', 'notification_status',
            'active_dentists', 'treatments_completed', 'active_plans', 'pending_invoice_count',
            'pending_balance', 'issued_invoice_count', 'issued_total', 'received_total',
            'cancelled_invoice_count', 'registered_income',
        ]);

        $data['appointment_trend'] = $metrics['appointment_trend']->map(fn ($row): array => [
            'day' => $row->day,
            'status' => $row->status->label(),
            'aggregate' => (int) $row->aggregate,
        ])->all();
        $data['recent_activity'] = $metrics['recent_activity']->map(fn ($event): array => [
            'appointment_id' => $event->appointment_id,
            'event_type' => $event->event_type,
            'patient' => trim(($event->appointment?->patient?->first_name ?? '').' '.($event->appointment?->patient?->last_name ?? '')),
            'when' => $event->created_at->timezone($timezone)->format('d/m H:i'),
        ])->all();
        $data['upcoming_appointments'] = $metrics['upcoming_appointments']->map(fn ($appointment): array => [
            'id' => $appointment->id,
            'patient' => trim(($appointment->patient?->first_name ?? '').' '.($appointment->patient?->last_name ?? '')),
            'when' => $appointment->starts_at->timezone($timezone)->locale('es')->translatedFormat('D d/m/Y, H:i'),
            'status' => $appointment->status->label(),
            'url' => route('citas.show', $appointment),
        ])->all();

        if (isset($metrics['treatment_trend'])) {
            $data['treatment_trend'] = $metrics['treatment_trend']->map(fn ($row): array => [
                'day' => $row->day,
                'aggregate' => (int) $row->aggregate,
            ])->all();
            $data['top_treatments'] = $metrics['top_treatments']->map(fn ($row): array => [
                'name' => $row->treatment?->name,
                'aggregate' => (int) $row->aggregate,
            ])->all();
        }

        if (isset($metrics['revenue_by_day'])) {
            $data['revenue_by_day'] = $metrics['revenue_by_day']->map(fn ($row): array => [
                'day' => $row->payment_date->format('d/m/Y'),
                'amount_cents' => (int) $row->amount_cents,
            ])->all();
            $data['dentist_activity'] = $metrics['dentist_activity']->map(fn (Dentist $dentist): array => [
                'id' => $dentist->id,
                'name' => $dentist->user?->name ?? 'Odontólogo #'.$dentist->id,
                'appointment_count' => (int) $dentist->appointment_count,
                'procedure_count' => (int) $dentist->procedure_count,
            ])->values()->all();
        }

        if (isset($metrics['pending_invoices'])) {
            $data['pending_invoices'] = $metrics['pending_invoices']->map(fn ($invoice): array => [
                'patient' => trim(($invoice->patient?->first_name ?? '').' '.($invoice->patient?->last_name ?? '')),
                'number' => $invoice->number,
                'balance_due' => (float) $invoice->balance_due,
                'url' => route('facturacion.facturas.show', $invoice),
            ])->all();
        }

        return $data;
    }
}
