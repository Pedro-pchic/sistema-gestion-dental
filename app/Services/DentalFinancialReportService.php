<?php

namespace App\Services;

use App\Enums\DentalInvoiceStatus;
use App\Enums\DentalPaymentStatus;
use App\Models\DentalInvoice;
use App\Models\DentalPayment;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class DentalFinancialReportService
{
    /** @return array{issued_invoice_count: int, issued_total: string, received_total: string, pending_invoice_count: int, pending_balance: string, cancelled_invoice_count: int} */
    public function summary(string $from, string $to): array
    {
        $toExclusive = CarbonImmutable::parse($to)->addDay()->toDateString();
        $timezone = config('appointments.timezone');
        $cancelledFrom = CarbonImmutable::createFromFormat('!Y-m-d', $from, $timezone)->utc();
        $cancelledTo = CarbonImmutable::createFromFormat('!Y-m-d', $to, $timezone)->addDay()->utc();
        $issued = DentalInvoice::query()
            ->whereIn('status', [DentalInvoiceStatus::Issued->value, DentalInvoiceStatus::PartiallyPaid->value, DentalInvoiceStatus::Paid->value])
            ->where('issue_date', '>=', $from)
            ->where('issue_date', '<', $toExclusive)
            ->selectRaw('COUNT(*) AS invoice_count')
            ->selectRaw('COALESCE(CAST(ROUND(SUM(total) * 100, 0) AS BIGINT), 0) AS cents')
            ->first();
        $pending = DentalInvoice::query()
            ->whereIn('status', [DentalInvoiceStatus::Issued->value, DentalInvoiceStatus::PartiallyPaid->value])
            ->where('balance_due', '>', 0)
            ->where('issue_date', '>=', $from)
            ->where('issue_date', '<', $toExclusive)
            ->selectRaw('COUNT(*) AS invoice_count')
            ->selectRaw('COALESCE(CAST(ROUND(SUM(balance_due) * 100, 0) AS BIGINT), 0) AS cents')
            ->first();
        $receivedCents = DentalPayment::query()
            ->where('status', DentalPaymentStatus::Registered->value)
            ->where('payment_date', '>=', $from)
            ->where('payment_date', '<', $toExclusive)
            ->selectRaw('COALESCE(CAST(ROUND(SUM(amount) * 100, 0) AS BIGINT), 0) AS cents')
            ->value('cents');

        return [
            'issued_invoice_count' => (int) $issued->invoice_count,
            'issued_total' => Money::fromCents((int) $issued->cents),
            'received_total' => Money::fromCents((int) $receivedCents),
            'pending_invoice_count' => (int) $pending->invoice_count,
            'pending_balance' => Money::fromCents((int) $pending->cents),
            'cancelled_invoice_count' => DentalInvoice::query()
                ->where('status', DentalInvoiceStatus::Cancelled->value)
                ->where('cancelled_at', '>=', $cancelledFrom)
                ->where('cancelled_at', '<', $cancelledTo)
                ->count(),
        ];
    }

    /** @return Collection<int, DentalPayment> */
    public function revenueByDay(string $from, string $to): Collection
    {
        $toExclusive = CarbonImmutable::parse($to)->addDay()->toDateString();

        return DentalPayment::query()
            ->where('status', DentalPaymentStatus::Registered->value)
            ->where('payment_date', '>=', $from)
            ->where('payment_date', '<', $toExclusive)
            ->select('payment_date')
            ->selectRaw('CAST(ROUND(SUM(amount) * 100, 0) AS BIGINT) AS amount_cents')
            ->groupBy('payment_date')
            ->orderBy('payment_date')
            ->get();
    }
}
