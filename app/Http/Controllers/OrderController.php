<?php

namespace App\Http\Controllers;

use App\Actions\ConfirmOrderAsSale;
use App\Actions\SaveOrder;
use App\Enums\OrderStatus;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\Quote;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(): View
    {
        $orders = Order::query()
            ->with('customer')
            ->orderByDesc('order_date')
            ->orderByDesc('id')
            ->paginate(15);

        return view('orders.index', compact('orders'));
    }

    public function create(): View
    {
        return view('orders.create', [
            'customers' => $this->activeCustomers(),
            'details' => [[]],
            'order' => null,
            'products' => $this->activeProducts(),
        ]);
    }

    public function store(StoreOrderRequest $request, SaveOrder $saveOrder): RedirectResponse
    {
        $order = $saveOrder->handle($request->validated());

        return redirect()
            ->route('pedidos.show', $order)
            ->with('status', 'Pedido creado como pendiente.');
    }

    public function storeFromQuote(Quote $quote, SaveOrder $saveOrder): RedirectResponse
    {
        $order = $saveOrder->handle([], sourceQuote: $quote);

        return redirect()
            ->route('pedidos.show', $order)
            ->with('status', 'Cotización aceptada convertida en pedido.');
    }

    public function show(Order $order): View
    {
        $order->load(['address', 'customer', 'quote', 'details.product', 'sale', 'statusHistories.changedBy']);
        $nextLogisticsStatus = $order->currentStatus()->nextLogisticsStage();

        return view('orders.show', compact('nextLogisticsStatus', 'order'));
    }

    public function edit(Order $order): View
    {
        abort_unless($order->isEditable(), 404);

        $order->load('details.product');
        $details = $order->details
            ->map(fn (OrderDetail $detail): array => [
                'product_id' => $detail->product_id,
                'quantity' => $detail->quantity,
                'unit_price' => $detail->unit_price,
                'subtotal' => $detail->subtotal,
            ])
            ->all();

        return view('orders.edit', [
            'customers' => $this->activeCustomers(),
            'details' => $details,
            'order' => $order,
            'products' => $this->activeProducts(),
        ]);
    }

    public function update(
        UpdateOrderRequest $request,
        Order $order,
        SaveOrder $saveOrder,
    ): RedirectResponse {
        $order = $saveOrder->handle($request->validated(), $order);

        return redirect()
            ->route('pedidos.show', $order)
            ->with('status', 'Pedido pendiente actualizado correctamente.');
    }

    public function confirm(Request $request, Order $order): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $order->transitionTo(OrderStatus::Confirmed, $user);

        return redirect()
            ->route('pedidos.show', $order)
            ->with('status', 'Pedido confirmado. Ya puede registrarse como venta.');
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $order->transitionTo(OrderStatus::Cancelled, $user);

        return redirect()
            ->route('pedidos.show', $order)
            ->with('status', 'Pedido cancelado sin modificar el inventario.');
    }

    public function sell(Request $request, Order $order, ConfirmOrderAsSale $confirmOrderAsSale): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        /** @var Sale $sale */
        $sale = $confirmOrderAsSale->handle($order, $user);

        return redirect()
            ->route('ventas.show', $sale)
            ->with('status', 'Venta registrada y salida de inventario aplicada.');
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
