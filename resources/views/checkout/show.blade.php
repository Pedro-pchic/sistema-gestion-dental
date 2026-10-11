@extends('layouts.store')

@section('title', 'Pedido confirmado')
@section('heading', 'Pedido recibido')
@section('subheading', 'Tu pedido entró al flujo de confirmación de '.config('app.name'))

@section('content')
    <section class="rounded-xl border border-emerald-200 bg-emerald-50 p-6">
        <p class="text-sm font-semibold uppercase tracking-wide text-emerald-700">Número de pedido</p>
        <p class="mt-1 text-2xl font-bold text-emerald-900">{{ $order->number }}</p>
    </section>

    <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
        <dl class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <dt class="text-sm font-medium text-stone-500">Fecha</dt>
                <dd class="mt-1 font-semibold">{{ $order->order_date->format('Y-m-d') }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-stone-500">Estado</dt>
                <dd class="mt-1 font-semibold">{{ $order->status->label() }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-sm font-medium text-stone-500">Dirección de entrega</dt>
                <dd class="mt-1">{{ $order->address->address }}, {{ $order->address->city }}, {{ $order->address->department }}</dd>
            </div>
        </dl>
    </section>

    <section class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-stone-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200 text-sm">
                <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">
                    <tr>
                        <th class="px-5 py-3">Producto</th>
                        <th class="px-5 py-3 text-right">Cantidad</th>
                        <th class="px-5 py-3 text-right">Precio</th>
                        <th class="px-5 py-3 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @foreach ($order->details as $detail)
                        <tr>
                            <td class="px-5 py-4 font-semibold">{{ $detail->product->name }}</td>
                            <td class="px-5 py-4 text-right tabular-nums">{{ $detail->quantity }}</td>
                            <td class="px-5 py-4 text-right tabular-nums">Q {{ number_format((float) $detail->unit_price, 2) }}</td>
                            <td class="px-5 py-4 text-right tabular-nums">Q {{ number_format((float) $detail->subtotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-stone-50">
                    <tr>
                        <th colspan="3" class="px-5 py-4 text-right text-base">Total</th>
                        <td class="px-5 py-4 text-right text-lg font-bold tabular-nums">Q {{ number_format((float) $order->total, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </section>

    <div>
        <a href="{{ route('catalogo.index') }}" class="rounded-lg bg-brand-700 px-5 py-2.5 font-semibold text-white hover:bg-brand-600">Volver al catálogo</a>
    </div>
@endsection
