<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Http\Requests\TrackOrderRequest;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderTrackingController extends Controller
{
    public function create(): View
    {
        return view('portal.tracking');
    }

    public function show(TrackOrderRequest $request): View
    {
        $data = $request->validated();
        $email = Str::lower($data['email']);

        $order = Order::query()
            ->where('number', $data['number'])
            ->whereHas('customer', function (Builder $query) use ($email): void {
                $query->whereRaw('LOWER(email) = ?', [$email]);
            })
            ->with(['sale', 'statusHistories'])
            ->first();

        if ($order === null) {
            throw ValidationException::withMessages([
                'number' => 'No encontramos un pedido con esos datos.',
            ]);
        }

        $currentStatus = $order->currentStatus();
        $currentIndex = array_search($currentStatus, OrderStatus::logisticsStages(), true);
        $historyByStatus = $order->statusHistories->keyBy(
            fn (OrderStatusHistory $history): string => $history->status->value,
        );
        $timeline = array_map(
            function (OrderStatus $status, int $index) use ($currentIndex, $currentStatus, $historyByStatus): array {
                $history = $historyByStatus->get($status->value);

                return [
                    'status' => $status,
                    'history' => $history,
                    'is_current' => $currentStatus === $status,
                    'is_complete' => $history instanceof OrderStatusHistory
                        || ($currentIndex !== false && $index <= $currentIndex),
                ];
            },
            OrderStatus::logisticsStages(),
            array_keys(OrderStatus::logisticsStages()),
        );

        return view('portal.tracking', compact('currentStatus', 'order', 'timeline'));
    }
}
