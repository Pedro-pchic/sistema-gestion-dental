<?php

namespace App\Http\Controllers;

use App\Enums\DentalInvoiceStatus;
use App\Enums\DentalPaymentStatus;
use App\Models\DentalInvoice;
use App\Models\DentalPayment;
use App\Services\DentalFinancialReportService;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DentalFinancialReportController extends Controller
{
    public function __invoke(Request $request, DentalFinancialReportService $financialReport): View
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

        $summary = $financialReport->summary($from, $to);
        $issuedInvoiceCents = Money::toCents($summary['issued_total']);
        $receivedCents = Money::toCents($summary['received_total']);
        $pendingInvoices = DentalInvoice::query()
            ->whereIn('status', [DentalInvoiceStatus::Issued->value, DentalInvoiceStatus::PartiallyPaid->value])
            ->where('balance_due', '>', 0)
            ->where('issue_date', '>=', $from)
            ->where('issue_date', '<', $toExclusive);
        $pendingCents = Money::toCents($summary['pending_balance']);
        $cancelledInvoices = $summary['cancelled_invoice_count'];

        $pendingByPatient = (clone $pendingInvoices)
            ->select('patient_id')
            ->selectRaw('CAST(ROUND(SUM(balance_due) * 100, 0) AS BIGINT) AS balance_cents')
            ->with('patient:id,first_name,last_name,record_number')
            ->groupBy('patient_id')
            ->havingRaw('SUM(balance_due) > 0')
            ->orderByDesc('balance_cents')
            ->limit(30)
            ->get();

        $revenueByDay = $financialReport->revenueByDay($from, $to);

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
}
