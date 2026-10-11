<?php

namespace App\Http\Controllers;

use App\Actions\ReceivePurchase;
use App\Actions\SavePurchaseDraft;
use App\Enums\PurchaseStatus;
use App\Http\Requests\StorePurchaseRequest;
use App\Http\Requests\UpdatePurchaseRequest;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function index(): View
    {
        $purchases = Purchase::query()
            ->with('supplier')
            ->orderByDesc('order_date')
            ->orderByDesc('id')
            ->paginate(15);

        return view('purchases.index', compact('purchases'));
    }

    public function create(): View
    {
        $suppliers = Supplier::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $products = $this->activeProducts();

        return view('purchases.create', [
            'details' => [[]],
            'products' => $products,
            'purchase' => null,
            'suppliers' => $suppliers,
        ]);
    }

    public function store(StorePurchaseRequest $request, SavePurchaseDraft $savePurchaseDraft): RedirectResponse
    {
        $purchase = $savePurchaseDraft->handle($request->validated());

        return redirect()
            ->route('compras.show', $purchase)
            ->with('status', 'Orden de compra creada como borrador.');
    }

    public function show(Purchase $purchase, Request $request): View
    {
        $purchase->load(['supplier', 'details.product']);
        $canManagePurchases = $request->user()->can('manage-purchases');
        $canReceivePurchases = $request->user()->can('receive-purchases');

        return view('purchases.show', compact('canManagePurchases', 'canReceivePurchases', 'purchase'));
    }

    public function edit(Purchase $purchase): View
    {
        abort_unless($purchase->isEditable(), 404);

        $purchase->load(['supplier', 'details.product']);
        $suppliers = Supplier::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $products = $this->activeProducts();
        $details = $purchase->details
            ->map(fn (PurchaseDetail $detail): array => [
                'product_id' => $detail->product_id,
                'quantity' => $detail->quantity,
                'unit_cost' => $detail->unit_cost,
                'subtotal' => $detail->subtotal,
            ])
            ->all();

        return view('purchases.edit', compact('details', 'products', 'purchase', 'suppliers'));
    }

    public function update(
        UpdatePurchaseRequest $request,
        Purchase $purchase,
        SavePurchaseDraft $savePurchaseDraft,
    ): RedirectResponse {
        $purchase = $savePurchaseDraft->handle($request->validated(), $purchase);

        return redirect()
            ->route('compras.show', $purchase)
            ->with('status', 'Borrador de compra actualizado correctamente.');
    }

    public function submit(Purchase $purchase): RedirectResponse
    {
        $purchase->transitionTo(PurchaseStatus::Pending);

        return redirect()
            ->route('compras.show', $purchase)
            ->with('status', 'Orden enviada y marcada como pendiente.');
    }

    public function cancel(Purchase $purchase): RedirectResponse
    {
        $purchase->transitionTo(PurchaseStatus::Cancelled);

        return redirect()
            ->route('compras.show', $purchase)
            ->with('status', 'Orden de compra cancelada.');
    }

    public function receive(
        Purchase $purchase,
        Request $request,
        ReceivePurchase $receivePurchase,
    ): RedirectResponse {
        $receivePurchase->handle($purchase, $request->user());

        return redirect()
            ->route('compras.show', $purchase)
            ->with('status', 'Mercadería recibida e inventario actualizado.');
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
