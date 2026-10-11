<?php

namespace App\Http\Controllers;

use App\Actions\LoadCart;
use App\Actions\RecordEcommerceEvent;
use App\Http\Requests\AddCartItemRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(LoadCart $loadCart): View
    {
        $cart = $loadCart->handle(session(LoadCart::SESSION_KEY, []));

        return view('cart.index', compact('cart'));
    }

    public function store(
        AddCartItemRequest $request,
        Product $product,
        RecordEcommerceEvent $recordEcommerceEvent,
    ): RedirectResponse {
        $cart = session(LoadCart::SESSION_KEY, []);
        $isNewCart = $cart === [];
        $quantity = ($cart[$product->getKey()] ?? 0) + $request->integer('quantity');

        $this->ensurePurchasable($product, $quantity);

        $cart[$product->getKey()] = $quantity;
        $request->session()->put(LoadCart::SESSION_KEY, $cart);

        if ($isNewCart) {
            $recordEcommerceEvent->recordCartStarted($request);
        }

        return redirect()->route('carrito.index')->with('status', 'Producto agregado al carrito.');
    }

    public function update(UpdateCartItemRequest $request, Product $product): RedirectResponse
    {
        $cart = session(LoadCart::SESSION_KEY, []);

        if (! array_key_exists($product->getKey(), $cart)) {
            abort(404);
        }

        $quantity = $request->integer('quantity');
        $this->ensurePurchasable($product, $quantity);

        $cart[$product->getKey()] = $quantity;
        $request->session()->put(LoadCart::SESSION_KEY, $cart);

        return back()->with('status', 'Cantidad actualizada.');
    }

    public function destroy(
        Request $request,
        Product $product,
        RecordEcommerceEvent $recordEcommerceEvent,
    ): RedirectResponse {
        $cart = session(LoadCart::SESSION_KEY, []);
        unset($cart[$product->getKey()]);
        $request->session()->put(LoadCart::SESSION_KEY, $cart);

        if ($cart === []) {
            $recordEcommerceEvent->resetCartLifecycle($request);
        }

        return back()->with('status', 'Producto eliminado del carrito.');
    }

    public function clear(Request $request, RecordEcommerceEvent $recordEcommerceEvent): RedirectResponse
    {
        $request->session()->forget(LoadCart::SESSION_KEY);
        $recordEcommerceEvent->resetCartLifecycle($request);

        return back()->with('status', 'Carrito vaciado.');
    }

    private function ensurePurchasable(Product $product, int $quantity): void
    {
        $product->loadMissing(['category', 'inventory']);

        if (! $product->is_active || ! $product->category->is_active) {
            throw ValidationException::withMessages([
                'product' => 'El producto ya no está disponible para compra.',
            ]);
        }

        if (! $product->inventory || $product->inventory->stock < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => 'No hay existencias suficientes para la cantidad solicitada.',
            ]);
        }
    }
}
