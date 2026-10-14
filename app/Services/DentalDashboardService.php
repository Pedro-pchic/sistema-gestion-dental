<?php

namespace App\Services;

use App\Contracts\SmsSender;
use App\Enums\AppointmentStatus;
use App\Enums\DentalInvoiceStatus;
use App\Enums\TreatmentPlanStatus;
use App\Models\Appointment;
use App\Models\AppointmentEvent;
use App\Models\AppointmentReminder;
use App\Models\DentalInvoice;
use App\Models\Dentist;
use App\Models\Patient;
use App\Models\PatientTreatment;
use App\Models\Role;
use App\Models\TreatmentPlan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class DentalDashboardService
{
    public function __construct(private SmsSender $smsSender, private DentalFinancialReportService $financialReport) {}

    /** @return array<string, mixed> */
    public function dashboard(User $user, string $from, string $to, ?int $selectedDentistId = null): array
    {
        $timezone = config('appointments.timezone');
        $rangeStart = CarbonImmutable::createFromFormat('!Y-m-d', $from, $timezone)->utc();
        $rangeEnd = CarbonImmutable::createFromFormat('!Y-m-d', $to, $timezone)->addDay()->utc();
        $localToday = CarbonImmutable::now($timezone)->startOfDay();
        $todayStart = $localToday->utc();
        $tomorrowStart = $localToday->addDay()->utc();
        $isAdministrator = $user->hasRole(Role::ADMINISTRATOR);
        $isReceptionist = $user->hasRole(Role::RECEPTIONIST);
        $isDentist = $user->hasRole(Role::DENTIST);
        $dentistId = $isDentist
            ? Dentist::query()->where('user_id', $user->getKey())->where('is_active', true)->value('id')
            : ($isAdministrator ? $selectedDentistId : null);

        $appointments = Appointment::query()
            ->where('starts_at', '>=', $rangeStart)
            ->where('starts_at', '<', $rangeEnd);

        if ($dentistId !== null || $isDentist) {
            $appointments->where('dentist_id', $dentistId ?? 0);
        }

        $appointmentStatuses = (clone $appointments)
            ->select('status')
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn (string|int $count): int => (int) $count)
            ->all();

        $todayAppointments = Appointment::query()
            ->where('starts_at', '>=', $todayStart)
            ->where('starts_at', '<', $tomorrowStart)
            ->where('status', AppointmentStatus::Scheduled->value);

        if ($dentistId !== null || $isDentist) {
            $todayAppointments->where('dentist_id', $dentistId ?? 0);
        }

        $appointmentsToday = $todayAppointments->count();

        $upcomingQuery = Appointment::query()
            ->with('patient:id,first_name,last_name')
            ->where('starts_at', '>', now('UTC'))
            ->where('starts_at', '<=', now('UTC')->addDays(7))
            ->whereIn('status', [AppointmentStatus::Scheduled->value, AppointmentStatus::Confirmed->value]);

        if ($dentistId !== null || $isDentist) {
            $upcomingQuery->where('dentist_id', $dentistId ?? 0);
        }

        $upcomingAppointments = $upcomingQuery
            ->orderBy('starts_at')
            ->orderBy('id')
            ->limit(10)
            ->get(['id', 'patient_id', 'dentist_id', 'starts_at', 'ends_at', 'status']);

        $recentActivityQuery = AppointmentEvent::query()
            ->with('appointment.patient:id,first_name,last_name')
            ->latest('id')
            ->limit(5);

        if ($dentistId !== null || $isDentist) {
            $recentActivityQuery->whereHas('appointment', fn ($query) => $query->where('dentist_id', $dentistId ?? 0));
        }

        $pendingAppointmentsQuery = Appointment::query()
            ->where('status', AppointmentStatus::Scheduled->value)
            ->where('starts_at', '>', now('UTC'))
            ->where('starts_at', '<=', now('UTC')->addDay());

        if ($dentistId !== null || $isDentist) {
            $pendingAppointmentsQuery->where('dentist_id', $dentistId ?? 0);
        }

        $patients = Patient::query();
        if ($dentistId !== null || $isDentist) {
            $patients->whereHas('dentists', fn ($query) => $query->where('dentists.id', $dentistId ?? 0));
        }

        $localDateExpression = DB::getDriverName() === 'pgsql' ? 'DATE(starts_at AT TIME ZONE ?)' : 'DATE(starts_at, ?)';
        $localDateBinding = DB::getDriverName() === 'pgsql' ? $timezone : $localToday->format('P');
        $appointmentTrend = (clone $appointments)
            ->select('status')
            ->selectRaw($localDateExpression.' AS day', [$localDateBinding])
            ->selectRaw('COUNT(*) AS aggregate')
            ->groupBy('day', 'status')
            ->orderBy('day')
            ->get();

        $metrics = [
            'role' => $user->role?->name,
            'active_patients' => (clone $patients)->where('is_active', true)->count(),
            'registered_patients' => (clone $patients)->where('created_at', '>=', $rangeStart)->where('created_at', '<', $rangeEnd)->count(),
            'appointments_today' => $appointmentsToday,
            'appointment_statuses' => $appointmentStatuses,
            'scheduled_appointments' => $appointmentStatuses[AppointmentStatus::Scheduled->value] ?? 0,
            'appointment_trend' => $appointmentTrend,
            'confirmed_appointments' => $appointmentStatuses[AppointmentStatus::Confirmed->value] ?? 0,
            'cancelled_appointments' => $appointmentStatuses[AppointmentStatus::Cancelled->value] ?? 0,
            'completed_appointments' => $appointmentStatuses[AppointmentStatus::Completed->value] ?? 0,
            'pending_appointments' => $pendingAppointmentsQuery->count(),
            'upcoming_appointments' => $upcomingAppointments,
            'recent_activity' => $recentActivityQuery->get(['id', 'appointment_id', 'event_type', 'created_at']),
            'notification_status' => $this->notificationStatus($isAdministrator || $isReceptionist),
        ];

        if ($isAdministrator) {
            $metrics['active_dentists'] = Dentist::query()->where('is_active', true)->count();
        }

        if ($isAdministrator || $isDentist) {
            $treatmentsQuery = PatientTreatment::query()
                ->where('performed_at', '>=', $rangeStart)
                ->where('performed_at', '<', $rangeEnd)
                ->where('performed_at', '<=', now('UTC'));

            if ($dentistId !== null || $isDentist) {
                $treatmentsQuery->where('dentist_id', $dentistId ?? 0);
            }

            $metrics['treatments_completed'] = $treatmentsQuery->count();
            $treatmentDateExpression = DB::getDriverName() === 'pgsql' ? 'DATE(performed_at AT TIME ZONE ?)' : 'DATE(performed_at, ?)';
            $metrics['treatment_trend'] = (clone $treatmentsQuery)
                ->selectRaw($treatmentDateExpression.' AS day', [$localDateBinding])
                ->selectRaw('COUNT(*) AS aggregate')
                ->groupBy('day')
                ->orderBy('day')
                ->get();
            $metrics['top_treatments'] = (clone $treatmentsQuery)
                ->select('treatment_id')
                ->selectRaw('COUNT(*) AS aggregate')
                ->with('treatment:id,name')
                ->groupBy('treatment_id')
                ->orderByDesc('aggregate')
                ->orderBy('treatment_id')
                ->limit(5)
                ->get();
            $plans = TreatmentPlan::query()
                ->whereIn('status', [TreatmentPlanStatus::Pending->value, TreatmentPlanStatus::InProgress->value])
                ->where('created_at', '>=', $rangeStart)
                ->where('created_at', '<', $rangeEnd);
            if ($dentistId !== null || $isDentist) {
                $plans->where('dentist_id', $dentistId ?? 0);
            }
            $metrics['active_plans'] = $plans->count();
        }

        if ($isAdministrator) {
            $metrics['dentist_activity'] = Dentist::query()
                ->select('id', 'user_id')
                ->with('user:id,name')
                ->withCount([
                    'appointments as appointment_count' => fn ($query) => $query->where('starts_at', '>=', $rangeStart)->where('starts_at', '<', $rangeEnd),
                    'treatmentProcedures as procedure_count' => fn ($query) => $query->where('performed_at', '>=', $rangeStart)->where('performed_at', '<', $rangeEnd)->where('performed_at', '<=', now('UTC')),
                ])
                ->when($dentistId !== null, fn ($query) => $query->whereKey($dentistId))
                ->orderByDesc('appointment_count')
                ->orderByDesc('procedure_count')
                ->limit(10)
                ->get()
                ->filter(fn (Dentist $dentist): bool => $dentist->appointment_count > 0 || $dentist->procedure_count > 0);
        }

        if ($isAdministrator || $isReceptionist) {
            $toExclusive = CarbonImmutable::parse($to)->addDay()->toDateString();
            $pendingInvoices = DentalInvoice::query()
                ->with('patient:id,first_name,last_name')
                ->whereIn('status', [DentalInvoiceStatus::Issued->value, DentalInvoiceStatus::PartiallyPaid->value])
                ->where('balance_due', '>', 0)
                ->where('issue_date', '>=', $from)
                ->where('issue_date', '<', $toExclusive)
                ->orderBy('issue_date')
                ->orderBy('id');

            if ($isReceptionist) {
                $metrics['pending_invoice_count'] = (clone $pendingInvoices)->count();
                $metrics['pending_balance'] = (clone $pendingInvoices)->sum('balance_due');
            }
            $metrics['pending_invoices'] = $pendingInvoices
                ->limit(5)
                ->get(['id', 'number', 'patient_id', 'balance_due', 'issue_date']);
        }

        if ($isAdministrator) {
            $metrics = array_merge($metrics, $this->financialReport->summary($from, $to));
            $metrics['registered_income'] = $metrics['received_total'];
            $metrics['revenue_by_day'] = $this->financialReport->revenueByDay($from, $to);
        }

        return $metrics;
    }

    /** @return array<string, int|string> */
    private function notificationStatus(bool $canSeeDeliveryResults): array
    {
        $emailEnabled = (bool) config('appointment_reminders.email.enabled');
        $mailDriver = config('mail.default');
        $smsEnabled = (bool) config('appointment_reminders.sms.enabled')
            && config('appointment_reminders.sms.driver') !== 'disabled'
            && $this->smsSender->isAvailable();
        $status = [
            'email' => ! $emailEnabled ? 'Desactivado' : ($mailDriver === 'log' ? 'Solo registro local' : 'Activo'),
            'sms' => $smsEnabled ? 'Proveedor habilitado' : 'Desactivado: sin proveedor',
            'queue' => config('queue.default') === 'sync' ? 'Síncrona' : 'Cola '.config('queue.default'),
        ];

        if ($canSeeDeliveryResults) {
            $status['pending'] = AppointmentReminder::query()
                ->whereIn('status', ['pending', 'queued', 'sending'])
                ->count();
            $status['failed_last_7_days'] = AppointmentReminder::query()
                ->where('status', 'failed')
                ->where('updated_at', '>=', now('UTC')->subDays(7))
                ->count();
        }

        return $status;
    }
}
