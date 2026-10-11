<?php

namespace App\Http\Controllers;

use App\Actions\CancelInvoice;
use App\Actions\IssueInvoice;
use App\Models\Invoice;
use App\Models\Sale;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(): View
    {
        $invoices = Invoice::query()
            ->with('sale.customer')
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->paginate(15);

        return view('invoices.index', compact('invoices'));
    }

    public function show(Invoice $invoice): View
    {
        $invoice->load([
            'sale.customer',
            'sale.details.product',
            'payments',
            'creditNotes',
        ]);
        $balanceDue = Money::fromCents($invoice->balanceDueCents());

        return view('invoices.show', compact('invoice', 'balanceDue'));
    }

    public function store(Sale $sale, IssueInvoice $issueInvoice): RedirectResponse
    {
        $invoice = $issueInvoice->handle($sale);

        return redirect()
            ->route('facturas.show', $invoice)
            ->with('status', 'La factura se emitió correctamente.');
    }

    public function cancel(Invoice $invoice, CancelInvoice $cancelInvoice): RedirectResponse
    {
        $cancelInvoice->handle($invoice);

        return redirect()
            ->route('facturas.show', $invoice)
            ->with('status', 'La factura se canceló y se conservó en el historial.');
    }

    public function document(Invoice $invoice): View
    {
        $invoice->load(['sale.customer', 'sale.details.product']);

        return view('documents.invoice', compact('invoice'));
    }
}
