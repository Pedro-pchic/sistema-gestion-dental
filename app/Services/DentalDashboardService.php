<?php

namespace App\Services;

use App\Contracts\SmsSender;
use App\Enums\AppointmentStatus;
use App\Enums\DentalInvoiceStatus;
use App\Enums\DentalPaymentStatus;
use App\Models\Appointment;
use App\Models\AppointmentEvent;
use App\Models\AppointmentReminder;
use App\Models\DentalInvoice;
use App\Models\DentalPayment;
use App\Models\Dentist;
use App\Models\Patient;
use App\Models\PatientTreatment;
use App\Models\Role;
use App\Models\User;
use Carbon\CarbonImmutable;

class DentalDashboardService
{
    public function __construct(private SmsSender $smsSender) {}

    /** @return array<string, mixed> */
    public function dashboard(User $user, string $from, string $to): array
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
            : null;

        $appointments = Appointment::query()
            ->where('starts_at', '>=', $rangeStart)
            ->where('starts_at', '<', $rangeEnd);

        if ($isDentist) {
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

        if ($isDentist) {
            $todayAppointments->where('dentist_id', $dentistId ?? 0);
        }

        $appointmentsToday = $todayAppointments->count();

        $upcomingQuery = Appointment::query()
            ->with('patient:id,first_name,last_name')
            ->where('starts_at', '>', now('UTC'))
            ->where('starts_at', '<=', now('UTC')->addDays(7))
            ->whereIn('status', [AppointmentStatus::Scheduled->value, AppointmentStatus::Confirmed->value]);

        if ($isDentist) {
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

        if ($isDentist) {
            $recentActivityQuery->whereHas('appointment', fn ($query) => $query->where('dentist_id', $dentistId ?? 0));
        }

        $pendingAppointmentsQuery = Appointment::query()
            ->where('status', AppointmentStatus::Scheduled->value)
            ->where('starts_at', '>', now('UTC'))
            ->where('starts_at', '<=', now('UTC')->addDay());

        if ($isDentist) {
            $pendingAppointmentsQuery->where('dentist_id', $dentistId ?? 0);
        }

        $metrics = [
            'role' => $user->role?->name,
            'active_patients' => $isDentist
                ? ($dentistId === null
                    ? 0
                    : Dentist::query()->find($dentistId)?->patients()->where('patients.is_active', true)->count() ?? 0)
                : Patient::query()->where('is_active', true)->count(),
            'appointments_today' => $appointmentsToday,
            'appointment_statuses' => $appointmentStatuses,
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
                ->where('performed_at', '<', $rangeEnd);

            if ($isDentist) {
                $treatmentsQuery->where('dentist_id', $dentistId ?? 0);
            }

            $metrics['treatments_completed'] = $treatmentsQuery->count();
        }

        if ($isAdministrator || $isReceptionist) {
            $pendingInvoices = DentalInvoice::query()
                ->with('patient:id,first_name,last_name')
                ->whereIn('status', [DentalInvoiceStatus::Issued->value, DentalInvoiceStatus::PartiallyPaid->value])
                ->where('balance_due', '>', 0)
                ->orderBy('issue_date')
                ->orderBy('id');

            $metrics['pending_invoice_count'] = (clone $pendingInvoices)->count();
            $metrics['pending_balance'] = (clone $pendingInvoices)->sum('balance_due');
            $metrics['pending_invoices'] = $pendingInvoices
                ->limit(5)
                ->get(['id', 'number', 'patient_id', 'balance_due', 'issue_date']);
        }

        if ($isAdministrator) {
            $metrics['issued_invoice_count'] = DentalInvoice::query()
                ->whereIn('status', [
                    DentalInvoiceStatus::Issued->value,
                    DentalInvoiceStatus::PartiallyPaid->value,
                    DentalInvoiceStatus::Paid->value,
                ])
                ->whereDate('issue_date', '>=', $from)
                ->whereDate('issue_date', '<', $rangeEnd->setTimezone($timezone)->toDateString())
                ->count();
            $metrics['registered_income'] = DentalPayment::query()
                ->where('status', DentalPaymentStatus::Registered->value)
                ->whereDate('payment_date', '>=', $from)
                ->whereDate('payment_date', '<=', $to)
                ->sum('amount');
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
