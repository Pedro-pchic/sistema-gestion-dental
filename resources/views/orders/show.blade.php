@extends('layouts.admin')

@section('title', 'Detalle de pedido')
@section('heading', $order->number)
@section('subheading', 'Pedido para '.$order->customer->name)

@section('actions')
    <div class="flex flex-wrap gap-3">
        @can('manage-commercial')
            @if ($order->isEditable())
                <a href="{{ route('pedidos.edit', $order) }}" class="rounded-lg border border-stone-300 bg-white px-4 py-2 text-sm font-semibold hover:bg-stone-50">Editar pedido</a>
                <form method="POST" action="{{ route('pedidos.confirm', $order) }}">
                    @csrf
                    <button type="submit" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600">Confirmar pedido</button>
                </form>
            @endif
            @if (in_array($order->status, [\App\Enums\OrderStatus::Pending, \App\Enums\OrderStatus::Confirmed], true))
                <form method="POST" action="{{ route('pedidos.cancel', $order) }}">
                    @csrf
                    <button type="submit" class="rounded-lg border border-red-200 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">Cancelar pedido</button>
                </form>
            @endif
            @if ($order->status === \App\Enums\OrderStatus::Confirmed)
                <form method="POST" action="{{ route('pedidos.sale.store', $order) }}">
                    @csrf
                    <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Registrar venta y salida</button>
                </form>
            @endif
            @if ($order->sale)
                <a href="{{ route('ventas.show', $order->sale) }}" class="rounded-lg border border-stone-300 bg-white px-4 py-2 text-sm font-semibold hover:bg-stone-50">Ver venta</a>
            @endif
        @endcan
        @can('manage-logistics')
            @if ($order->sale?->status === \App\Enums\SaleStatus::Confirmed)
                <a href="{{ route('operaciones.logistica.index') }}" class="rounded-lg border border-stone-300 bg-white px-4 py-2 text-sm font-semibold hover:bg-stone-50">Operaciones logísticas</a>
            @endif
        @endcan
    </div>
@endsection

@section('content')
    <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
        <dl class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <dt class="text-sm font-medium text-stone-500">Cliente</dt>
                <dd class="mt-1">
                    @can('manage-commercial')
                        <a href="{{ route('clientes.show', $order->customer) }}" class="font-semibold text-brand-700 hover:text-brand-500">{{ $order->customer->name }}</a>
                    @else
                        {{ $order->customer->name }}
                    @endcan
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-stone-500">Fecha</dt>
                <dd class="mt-1">{{ $order->order_date->format('Y-m-d') }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-stone-500">Estado</dt>
                <dd class="mt-1 font-semibold">{{ $order->currentStatus()->label() }}</dd>
            </div>
            <div>
                @can('manage-commercial')
                    <dt class="text-sm font-medium text-stone-500">Cotización</dt>
                    <dd class="mt-1">
                        @if ($order->quote)
                            <a href="{{ route('cotizaciones.show', $order->quote) }}" class="font-semibold text-brand-700 hover:text-brand-500">{{ $order->quote->number }}</a>
                        @else
                            Sin cotización
                        @endif
                    </dd>
                @endcan
            </div>
            <div>
                <dt class="text-sm font-medium text-stone-500">Origen</dt>
                <dd class="mt-1">{{ $order->origin === 'web' ? 'Tienda web' : 'Administrativo' }}</dd>
            </div>
            @if ($order->address)
                <div class="sm:col-span-2 lg:col-span-3">
                    <dt class="text-sm font-medium text-stone-500">Dirección de entrega</dt>
                    <dd class="mt-1">
                        {{ $order->address->address }}, {{ $order->address->city }}, {{ $order->address->department }}
                    </dd>
                </div>
            @endif
            <div class="sm:col-span-2 lg:col-span-4">
                <dt class="text-sm font-medium text-stone-500">Notas</dt>
                <dd class="mt-1 whitespace-pre-line">{{ $order->notes ?? 'Sin notas' }}</dd>
            </div>
        </dl>
    </section>

    <section class="grid gap-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-stone-200 lg:grid-cols-2">
            <div class="grid content-start gap-5">
                <div>
                    <p class="text-sm font-medium text-stone-500">Estado actual</p>
                    <p class="mt-1 text-lg font-bold text-stone-950">{{ $order->currentStatus()->label() }}</p>
                    @if ($order->isDelayed())
                        <p class="mt-2 inline-flex rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-800">Entrega retrasada</p>
                    @endif
                </div>

                <dl class="grid gap-4 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="font-medium text-stone-500">Entrega estimada</dt>
                        <dd class="mt-1">{{ $order->estimated_delivery_at?->format('d/m/Y H:i') ?? 'Sin programar' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-stone-500">Despachado</dt>
                        <dd class="mt-1">{{ $order->dispatched_at?->format('d/m/Y H:i') ?? 'Pendiente' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-stone-500">Entregado</dt>
                        <dd class="mt-1">{{ $order->delivered_at?->format('d/m/Y H:i') ?? 'Pendiente' }}</dd>
                    </div>
                </dl>

                @can('manage-logistics')
                    @if ($order->sale?->status === \App\Enums\SaleStatus::Confirmed)
                        @if ($order->currentStatus() !== \App\Enums\OrderStatus::Delivered)
                            <form method="POST" action="{{ route('operaciones.logistica.estimate', $order) }}" class="grid gap-3 border-t border-stone-200 pt-5">
                                @csrf
                                @method('PATCH')
                                <label for="estimated_delivery_at" class="text-sm font-semibold text-stone-700">Actualizar entrega estimada</label>
                                <div class="flex flex-wrap gap-3">
                                    <input id="estimated_delivery_at" name="estimated_delivery_at" type="datetime-local" value="{{ old('estimated_delivery_at', $order->estimated_delivery_at?->format('Y-m-d\\TH:i')) }}" class="min-w-0 flex-1 rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
                                    <button type="submit" class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold hover:bg-stone-50">Guardar fecha</button>
                                </div>
                                @error('estimated_delivery_at') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                            </form>
                        @endif

                        @if ($nextLogisticsStatus)
                            <form method="POST" action="{{ route('operaciones.logistica.advance', $order) }}" class="grid gap-3 border-t border-stone-200 pt-5">
                                @csrf
                                <label for="logistics-note" class="text-sm font-semibold text-stone-700">Nota interna (opcional)</label>
                                <textarea id="logistics-note" name="note" rows="2" maxlength="1000" class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-200">{{ old('note') }}</textarea>
                                @error('note') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                                <button type="submit" class="justify-self-start rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600">Marcar {{ mb_strtolower($nextLogisticsStatus->label()) }}</button>
                            </form>
                        @endif
                    @endif
                @endcan
            </div>

            <div>
                <h2 class="text-lg font-bold text-stone-950">Historial de seguimiento</h2>
                @if ($order->statusHistories->isEmpty())
                    <p class="mt-3 text-sm text-stone-500">No hay cambios de estado registrados.</p>
                @else
                    <ol class="mt-4 grid gap-4">
                        @foreach ($order->statusHistories as $history)
                            <li class="border-l-2 border-brand-200 pl-4">
                                <p class="font-semibold text-stone-900">{{ $history->status->label() }}</p>
                                <p class="mt-1 text-xs text-stone-500">
                                    {{ $history->occurred_at->format('d/m/Y H:i') }}
                                    @if ($history->changedBy)
                                        · {{ $history->changedBy->name }}
                                    @endif
                                </p>
                                @if ($history->note)
                                    <p class="mt-2 whitespace-pre-line text-sm text-stone-600">{{ $history->note }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>
    </section>

    <section class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-stone-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200 text-sm">
                <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">
                    <tr>
                        <th class="px-5 py-3">Producto</th>
                        <th class="px-5 py-3 text-right">Cantidad</th>
                        @can('manage-commercial')
                            <th class="px-5 py-3 text-right">Precio unitario</th>
                            <th class="px-5 py-3 text-right">Subtotal</th>
                        @endcan
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @foreach ($order->details as $detail)
                        <tr>
                            <td class="px-5 py-4">
                                <p class="font-semibold">{{ $detail->product->name }}</p>
                                <p class="mt-1 text-xs text-stone-500">{{ $detail->product->sku }}</p>
                            </td>
                            <td class="px-5 py-4 text-right tabular-nums">{{ $detail->quantity }}</td>
                            @can('manage-commercial')
                                <td class="px-5 py-4 text-right tabular-nums">Q {{ number_format((float) $detail->unit_price, 2) }}</td>
                                <td class="px-5 py-4 text-right tabular-nums">Q {{ number_format((float) $detail->subtotal, 2) }}</td>
                            @endcan
                        </tr>
                    @endforeach
                </tbody>
                @can('manage-commercial')
                    <tfoot class="bg-stone-50">
                        <tr>
                            <th colspan="3" class="px-5 py-4 text-right text-base">Total</th>
                            <td class="px-5 py-4 text-right text-base font-bold tabular-nums">Q {{ number_format((float) $order->total, 2) }}</td>
                        </tr>
                    </tfoot>
                @endcan
            </table>
        </div>
    </section>

    <div>
        @can('manage-commercial')
            <a href="{{ route('pedidos.index') }}" class="rounded-lg border border-stone-300 bg-white px-4 py-2 text-sm font-semibold hover:bg-stone-50">Volver a pedidos</a>
        @else
            <a href="{{ route('operaciones.logistica.index') }}" class="rounded-lg border border-stone-300 bg-white px-4 py-2 text-sm font-semibold hover:bg-stone-50">Volver a logística</a>
        @endcan
    </div>
@endsection
