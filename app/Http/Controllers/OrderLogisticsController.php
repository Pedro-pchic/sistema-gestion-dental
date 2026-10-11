<?php

namespace App\Http\Controllers;

use App\Actions\AdvanceOrderLogistics;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderLogisticsController extends Controller
{
    public function index(Request $request): View
    {
        $statusValues = array_map(
            fn (OrderStatus $status): string => $status->value,
            OrderStatus::logisticsStages(),
        );
        $filters = $request->validate([
            'status' => ['nullable', Rule::in($statusValues)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $counts = $this->eligibleOrders()
            ->select('logistics_status')
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('logistics_status')
            ->get()
            ->mapWithKeys(fn (Order $order): array => [
                $order->logistics_status?->value ?? OrderStatus::Confirmed->value => (int) $order->total,
            ]);

        $statusOptions = array_map(
            fn (OrderStatus $status): array => [
                'value' => $status->value,
                'label' => $status === OrderStatus::Confirmed ? 'Pendientes de preparación' : $status->label(),
                'count' => (int) ($counts->get($status->value) ?? 0),
            ],
            OrderStatus::logisticsStages(),
        );

        $ordersQuery = $this->eligibleOrders()
            ->with(['customer', 'sale'])
            ->orderByDesc('order_date')
            ->orderByDesc('id');

        if (isset($filters['status'])) {
            $selectedStatus = OrderStatus::from($filters['status']);

            if ($selectedStatus === OrderStatus::Confirmed) {
                $ordersQuery->whereNull('logistics_status');
            } else {
                $ordersQuery->where('logistics_status', $selectedStatus->value);
            }
        }

        if (isset($filters['from'])) {
            $ordersQuery->whereDate('order_date', '>=', $filters['from']);
        }

        if (isset($filters['to'])) {
            $ordersQuery->whereDate('order_date', '<=', $filters['to']);
        }

        $orders = $ordersQuery->paginate(15)->withQueryString();

        return view('logistics.index', compact('filters', 'orders', 'statusOptions'));
    }

    public function show(Order $order): View
    {
        $order->load([
            'address',
            'customer',
            'quote',
            'details.product',
            'sale',
            'statusHistories.changedBy',
        ]);

        abort_unless($this->isEligibleForLogistics($order), 404);

        $nextLogisticsStatus = $order->currentStatus()->nextLogisticsStage();

        return view('orders.show', compact('nextLogisticsStatus', 'order'));
    }

    public function advance(
        Request $request,
        Order $order,
        AdvanceOrderLogistics $advanceOrderLogistics,
    ): RedirectResponse {
        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $nextStatus = $advanceOrderLogistics->handle($order, $user, $validated['note'] ?? null);

        return redirect()
            ->route('operaciones.logistica.show', $order)
            ->with('status', 'Pedido actualizado: '.$nextStatus->label().'.');
    }

    public function estimate(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'estimated_delivery_at' => ['nullable', 'date'],
        ]);
        $order->loadMissing('sale');

        abort_unless(
            $this->isEligibleForLogistics($order)
                && $order->currentStatus() !== OrderStatus::Delivered,
            404,
        );

        $order->update([
            'estimated_delivery_at' => $validated['estimated_delivery_at'] ?? null,
        ]);

        return redirect()
            ->route('operaciones.logistica.show', $order)
            ->with('status', 'Fecha estimada de entrega actualizada.');
    }

    private function eligibleOrders(): Builder
    {
        return Order::query()->eligibleForLogistics();
    }

    private function isEligibleForLogistics(Order $order): bool
    {
        return $order->status === OrderStatus::Completed
            && $order->sale?->status === SaleStatus::Confirmed;
    }
}
