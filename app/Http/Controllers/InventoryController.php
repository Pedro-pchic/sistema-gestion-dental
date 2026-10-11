<?php

namespace App\Http\Controllers;

use App\Enums\InventoryMovementType;
use App\Http\Requests\InventoryMovementRequest;
use App\Http\Requests\UpdateMinimumStockRequest;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $search = trim($filters['search'] ?? '');

        $inventories = Inventory::query()
            ->with('product.category')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $term = '%'.$search.'%';

                $query->whereHas('product', function (Builder $productQuery) use ($term): void {
                    $productQuery->where(function (Builder $productQuery) use ($term): void {
                        $productQuery
                            ->where('sku', 'like', $term)
                            ->orWhere('name', 'like', $term)
                            ->orWhereHas('category', fn (Builder $categoryQuery): Builder => $categoryQuery
                                ->where('name', 'like', $term));
                    });
                });
            })
            ->orderBy('product_id')
            ->paginate(15)
            ->withQueryString();

        return view('inventory.index', compact('inventories', 'search'));
    }

    public function movements(Request $request): View
    {
        $filters = $request->validate([
            'product_id' => ['nullable', 'integer', Rule::exists('products', 'id')],
        ]);
        $productId = isset($filters['product_id']) ? (int) $filters['product_id'] : null;

        $products = Product::query()
            ->with('inventory')
            ->orderBy('name')
            ->get();

        $movements = InventoryMovement::query()
            ->with(['inventory.product.category', 'user'])
            ->when($productId !== null, fn (Builder $query): Builder => $query
                ->whereHas('inventory', fn (Builder $inventoryQuery): Builder => $inventoryQuery
                    ->where('product_id', $productId)))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $movementTypes = InventoryMovementType::cases();

        return view('inventory.movements', compact('movementTypes', 'movements', 'productId', 'products'));
    }

    public function storeMovement(InventoryMovementRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $inventory = Inventory::query()
            ->where('product_id', $data['product_id'])
            ->firstOrFail();

        $inventory->recordMovement(
            InventoryMovementType::from($data['type']),
            (int) $data['quantity'],
            $data['reason'] ?? null,
            $request->user(),
        );

        return redirect()
            ->route('inventario.movimientos.index', ['product_id' => $inventory->product_id])
            ->with('status', 'Movimiento de inventario registrado correctamente.');
    }

    public function updateMinimumStock(
        UpdateMinimumStockRequest $request,
        Inventory $inventory,
    ): RedirectResponse {
        $inventory->update($request->validated());

        return back()->with('status', 'Stock mínimo actualizado correctamente.');
    }
}
