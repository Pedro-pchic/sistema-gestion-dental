<?php

namespace App\Http\Controllers;

use App\Actions\SaveQuoteDraft;
use App\Enums\QuoteStatus;
use App\Http\Requests\StoreQuoteRequest;
use App\Http\Requests\UpdateQuoteRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteDetail;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class QuoteController extends Controller
{
    public function index(): View
    {
        $quotes = Quote::query()
            ->with('customer')
            ->orderByDesc('quote_date')
            ->orderByDesc('id')
            ->paginate(15);

        return view('quotes.index', compact('quotes'));
    }

    public function create(): View
    {
        return view('quotes.create', [
            'customers' => $this->activeCustomers(),
            'details' => [[]],
            'products' => $this->activeProducts(),
            'quote' => null,
        ]);
    }

    public function store(StoreQuoteRequest $request, SaveQuoteDraft $saveQuoteDraft): RedirectResponse
    {
        $quote = $saveQuoteDraft->handle($request->validated());

        return redirect()
            ->route('cotizaciones.show', $quote)
            ->with('status', 'Cotización creada como borrador.');
    }

    public function show(Quote $quote): View
    {
        $quote->load(['customer', 'details.product', 'order']);

        return view('quotes.show', compact('quote'));
    }

    public function edit(Quote $quote): View
    {
        abort_unless($quote->isEditable(), 404);

        $quote->load(['customer', 'details.product']);
        $details = $quote->details
            ->map(fn (QuoteDetail $detail): array => [
                'product_id' => $detail->product_id,
                'quantity' => $detail->quantity,
                'unit_price' => $detail->unit_price,
                'subtotal' => $detail->subtotal,
            ])
            ->all();

        return view('quotes.edit', [
            'customers' => $this->activeCustomers(),
            'details' => $details,
            'products' => $this->activeProducts(),
            'quote' => $quote,
        ]);
    }

    public function update(
        UpdateQuoteRequest $request,
        Quote $quote,
        SaveQuoteDraft $saveQuoteDraft,
    ): RedirectResponse {
        $quote = $saveQuoteDraft->handle($request->validated(), $quote);

        return redirect()
            ->route('cotizaciones.show', $quote)
            ->with('status', 'Borrador de cotización actualizado correctamente.');
    }

    public function send(Quote $quote): RedirectResponse
    {
        $quote->transitionTo(QuoteStatus::Sent);

        return redirect()
            ->route('cotizaciones.show', $quote)
            ->with('status', 'Cotización enviada correctamente.');
    }

    public function accept(Quote $quote): RedirectResponse
    {
        $quote->transitionTo(QuoteStatus::Accepted);

        return redirect()
            ->route('cotizaciones.show', $quote)
            ->with('status', 'Cotización marcada como aceptada.');
    }

    public function reject(Quote $quote): RedirectResponse
    {
        $quote->transitionTo(QuoteStatus::Rejected);

        return redirect()
            ->route('cotizaciones.show', $quote)
            ->with('status', 'Cotización marcada como rechazada.');
    }

    /**
     * @return Collection<int, Customer>
     */
    private function activeCustomers(): Collection
    {
        return Customer::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Product>
     */
    private function activeProducts(): Collection
    {
        return Product::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
