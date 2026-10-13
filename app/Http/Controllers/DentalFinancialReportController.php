<?php

namespace App\Http\Controllers;

use App\Enums\DentalInvoiceStatus;
use App\Enums\DentalPaymentStatus;
use App\Models\DentalInvoice;
use App\Models\DentalPayment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DentalFinancialReportController extends Controller
{
    public function __invoke(Request $request): View
    {
        Gate::authorize('view-dental-reports');

        $filters = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $from = $filters['from'] ?? today()->startOfMonth()->toDateString();
        $to = $filters['to'] ?? today()->toDateString();
        $toExclusive = Carbon::parse($to)->addDay()->toDateString();

        if ($from > $to) {
            throw ValidationException::withMessages([
                'to' => 'La fecha final debe ser igual o posterior a la fecha inicial.',
            ]);
        }

        $issuedStatuses = [
            DentalInvoiceStatus::Issued->value,
            DentalInvoiceStatus::PartiallyPaid->value,
            DentalInvoiceStatus::Paid->value,
        ];
        $issuedInvoices = DentalInvoice::query()
            ->whereIn('status', $issuedStatuses)
            ->where('issue_date', '>=', $from)
            ->where('issue_date', '<', $toExclusive);
        $payments = DentalPayment::query()
            ->where('status', DentalPaymentStatus::Registered->value)
            ->where('payment_date', '>=', $from)
            ->where('payment_date', '<', $toExclusive);

        $issuedInvoiceCents = $this->sumCents(clone $issuedInvoices, 'total');
        $receivedCents = $this->sumCents(clone $payments, 'amount');
        $pendingInvoices = DentalInvoice::query()
            ->whereIn('status', [DentalInvoiceStatus::Issued->value, DentalInvoiceStatus::PartiallyPaid->value])
            ->where('issue_date', '>=', $from)
            ->where('issue_date', '<', $toExclusive);
        $pendingCents = $this->sumCents(clone $pendingInvoices, 'balance_due');
        $cancelledInvoices = DentalInvoice::query()
            ->where('status', DentalInvoiceStatus::Cancelled->value)
            ->whereDate('cancelled_at', '>=', $from)
            ->whereDate('cancelled_at', '<=', $to)
            ->count();

        $pendingByPatient = (clone $pendingInvoices)
            ->select('patient_id')
            ->selectRaw('CAST(ROUND(SUM(balance_due) * 100, 0) AS INTEGER) AS balance_cents')
            ->with('patient:id,first_name,last_name,record_number')
            ->groupBy('patient_id')
            ->havingRaw('SUM(balance_due) > 0')
            ->orderByDesc('balance_cents')
            ->limit(30)
            ->get();

        $revenueByDay = (clone $payments)
            ->select('payment_date')
            ->selectRaw('CAST(ROUND(SUM(amount) * 100, 0) AS INTEGER) AS amount_cents')
            ->groupBy('payment_date')
            ->orderBy('payment_date')
            ->get();

        return view('dental-reports.index', compact(
            'from',
            'to',
            'issuedInvoices',
            'payments',
            'pendingInvoices',
            'issuedInvoiceCents',
            'receivedCents',
            'pendingCents',
            'cancelledInvoices',
            'pendingByPatient',
            'revenueByDay',
        ));
    }

    private function sumCents(Builder $query, string $column): int
    {
        return (int) ($query
            ->selectRaw('COALESCE(CAST(ROUND(SUM('.$column.') * 100, 0) AS INTEGER), 0) AS cents')
            ->value('cents') ?? 0);
    }
}
