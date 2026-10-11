@extends('layouts.admin')

@section('title', 'Pedidos')
@section('heading', 'Pedidos')
@section('subheading', 'Pedidos comerciales pendientes de surtir y completar')

@section('actions')
    <a href="{{ route('pedidos.create') }}" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600">Nuevo pedido</a>
@endsection

@section('content')
    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-stone-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200 text-sm">
                <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">
                    <tr>
                        <th class="px-5 py-3">Número</th>
                        <th class="px-5 py-3">Cliente</th>
                        <th class="px-5 py-3">Fecha</th>
                        <th class="px-5 py-3 text-right">Total</th>
                        <th class="px-5 py-3">Estado</th>
                        <th class="px-5 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($orders as $order)
                        <tr>
                            <td class="px-5 py-4 font-semibold">
                                <a href="{{ route('pedidos.show', $order) }}" class="text-brand-700 hover:text-brand-500">{{ $order->number }}</a>
                            </td>
                            <td class="px-5 py-4">{{ $order->customer->name }}</td>
                            <td class="px-5 py-4">{{ $order->order_date->format('Y-m-d') }}</td>
                            <td class="px-5 py-4 text-right tabular-nums">Q {{ number_format((float) $order->total, 2) }}</td>
                            <td class="px-5 py-4">
                                @php
                                    $statusClass = match ($order->status) {
                                        \App\Enums\OrderStatus::Pending => 'bg-amber-100 text-amber-900',
                                        \App\Enums\OrderStatus::Confirmed => 'bg-brand-100 text-brand-800',
                                        \App\Enums\OrderStatus::Cancelled => 'bg-red-100 text-red-800',
                                        \App\Enums\OrderStatus::Completed => 'bg-emerald-100 text-emerald-800',
                                    };
                                @endphp
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset ring-current/10 {{ $statusClass }}">{{ $order->status->label() }}</span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('pedidos.show', $order) }}" class="font-semibold text-brand-700 hover:text-brand-500">Ver</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-stone-500">Todavía no hay pedidos registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($orders->hasPages())
            <div class="border-t border-stone-200 px-5 py-4">{{ $orders->links() }}</div>
        @endif
    </div>
@endsection
