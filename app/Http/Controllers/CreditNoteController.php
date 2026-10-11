<?php

namespace App\Http\Controllers;

use App\Actions\CancelCreditNote;
use App\Actions\IssueCreditNote;
use App\Http\Requests\StoreCreditNoteRequest;
use App\Models\CreditNote;
use App\Models\Invoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CreditNoteController extends Controller
{
    public function index(): View
    {
        $creditNotes = CreditNote::query()
            ->with('invoice.sale.customer')
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->paginate(15);

        return view('credit-notes.index', compact('creditNotes'));
    }

    public function show(CreditNote $creditNote): View
    {
        $creditNote->load('invoice.sale.customer');

        return view('credit-notes.show', compact('creditNote'));
    }

    public function store(
        Invoice $invoice,
        StoreCreditNoteRequest $request,
        IssueCreditNote $issueCreditNote,
    ): RedirectResponse {
        $data = $request->validated();
        $creditNote = $issueCreditNote->handle(
            $invoice,
            (string) $data['amount'],
            $data['reason'],
            $data['issue_date'],
        );

        return redirect()
            ->route('notas-credito.show', $creditNote)
            ->with('status', 'La nota de crédito se emitió correctamente.');
    }

    public function cancel(CreditNote $creditNote, CancelCreditNote $cancelCreditNote): RedirectResponse
    {
        $cancelCreditNote->handle($creditNote);

        return redirect()
            ->route('notas-credito.show', $creditNote)
            ->with('status', 'La nota de crédito se canceló y se conservó en el historial.');
    }

    public function document(CreditNote $creditNote): View
    {
        $creditNote->load('invoice.sale.customer');

        return view('documents.credit-note', compact('creditNote'));
    }
}
