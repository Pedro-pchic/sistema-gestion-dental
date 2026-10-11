@extends('layouts.admin')

@section('title', 'Operaciones logísticas')
@section('heading', 'Operaciones / Logística')
@section('subheading', 'Prepara, despacha y da seguimiento a los pedidos con venta confirmada.')

@section('content')
    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3" aria-label="Pedidos por estado logístico">
        @foreach ($statusOptions as $option)
            <a href="{{ route('operaciones.logistica.index', array_merge(request()->only(['from', 'to']), ['status' => $option['value']])) }}" @class([
                'rounded-xl border p-4 shadow-sm transition',
                'border-brand-300 bg-brand-50 ring-2 ring-brand-200' => ($filters['status'] ?? null) === $option['value'],
                'border-stone-200 bg-white hover:border-brand-200 hover:bg-stone-50' => ($filters['status'] ?? null) !== $option['value'],
            ])>
                <span class="text-sm font-semibold text-stone-600">{{ $option['label'] }}</span>
                <span class="mt-2 block text-2xl font-bold text-stone-950">{{ number_format($option['count']) }}</span>
            </a>
        @endforeach
    </section>

    <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-stone-200">
        <form method="GET" action="{{ route('operaciones.logistica.index') }}" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4 xl:items-end">
            <div>
                <label for="status" class="text-sm font-semibold text-stone-700">Estado</label>
                <select id="status" name="status" class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
                    <option value="">Todos</option>
                    @foreach ($statusOptions as $option)
                        <option value="{{ $option['value'] }}" @selected(($filters['status'] ?? null) === $option['value'])>{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="from" class="text-sm font-semibold text-stone-700">Desde</label>
                <input id="from" name="from" type="date" value="{{ $filters['from'] ?? '' }}" class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
            </div>
            <div>
                <label for="to" class="text-sm font-semibold text-stone-700">Hasta</label>
                <input id="to" name="to" type="date" value="{{ $filters['to'] ?? '' }}" class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="submit" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600">Filtrar</button>
                <a href="{{ route('operaciones.logistica.index') }}" class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700 hover:bg-stone-50">Limpiar</a>
            </div>
        </form>
        @error('status') <p class="mt-3 text-sm text-red-700">{{ $message }}</p> @enderror
        @error('from') <p class="mt-3 text-sm text-red-700">{{ $message }}</p> @enderror
        @error('to') <p class="mt-3 text-sm text-red-700">{{ $message }}</p> @enderror
    </section>

    <section class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-stone-200" aria-label="Pedidos en logística">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200 text-sm">
                <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">
                    <tr>
                        <th scope="col" class="px-5 py-3">Pedido</th>
                        <th scope="col" class="px-5 py-3">Cliente</th>
                        <th scope="col" class="px-5 py-3">Fecha</th>
                        <th scope="col" class="px-5 py-3">Estado</th>
                        <th scope="col" class="px-5 py-3">Entrega estimada</th>
                        <th scope="col" class="px-5 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($orders as $order)
                        <tr>
                            <td class="whitespace-nowrap px-5 py-4">
                                <a href="{{ route('operaciones.logistica.show', $order) }}" class="font-semibold text-brand-700 hover:text-brand-500">{{ $order->number }}</a>
                            </td>
                            <td class="px-5 py-4">{{ $order->customer->name }}</td>
                            <td class="whitespace-nowrap px-5 py-4">{{ $order->order_date->format('d/m/Y') }}</td>
                            <td class="whitespace-nowrap px-5 py-4">
                                <span @class([
                                    'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
                                    'bg-amber-100 text-amber-900' => $order->currentStatus() === \App\Enums\OrderStatus::Confirmed,
                                    'bg-brand-100 text-brand-800' => in_array($order->currentStatus(), [\App\Enums\OrderStatus::Preparing, \App\Enums\OrderStatus::Packed], true),
                                    'bg-sky-100 text-sky-800' => in_array($order->currentStatus(), [\App\Enums\OrderStatus::Dispatched, \App\Enums\OrderStatus::InTransit], true),
                                    'bg-emerald-100 text-emerald-800' => $order->currentStatus() === \App\Enums\OrderStatus::Delivered,
                                ])>
                                    {{ $order->currentStatus() === \App\Enums\OrderStatus::Confirmed ? 'Pendiente de preparación' : $order->currentStatus()->label() }}
                                </span>
                                @if ($order->isDelayed())
                                    <span class="ml-1 inline-flex rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-800">Retrasado</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-4">{{ $order->estimated_delivery_at?->format('d/m/Y H:i') ?? 'Sin programar' }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-right">
                                <div class="flex flex-wrap justify-end gap-2">
                                    @if ($order->currentStatus()->nextLogisticsStage())
                                        <form method="POST" action="{{ route('operaciones.logistica.advance', $order) }}">
                                            @csrf
                                            <button type="submit" class="rounded-lg bg-brand-700 px-3 py-2 text-xs font-semibold text-white hover:bg-brand-600">
                                                Marcar {{ mb_strtolower($order->currentStatus()->nextLogisticsStage()->label()) }}
                                            </button>
                                        </form>
                                    @endif
                                    <a href="{{ route('operaciones.logistica.show', $order) }}" class="rounded-lg border border-stone-300 px-3 py-2 text-xs font-semibold hover:bg-stone-50">Ver detalle</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-stone-500">No hay pedidos que coincidan con estos filtros.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($orders->hasPages())
            <div class="border-t border-stone-200 px-5 py-4">{{ $orders->links() }}</div>
        @endif
    </section>
@endsection
