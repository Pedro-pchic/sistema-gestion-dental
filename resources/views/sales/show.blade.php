@extends('layouts.admin')

@section('title', 'Detalle de venta')
@section('heading', $sale->number)
@section('subheading', 'Venta para '.$sale->customer->name)

@section('actions')
    @if ($sale->invoice)
        <a href="{{ route('facturas.show', $sale->invoice) }}" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Ver factura {{ $sale->invoice->number }}</a>
    @elseif ($sale->status->value === 'confirmed')
        <form method="POST" action="{{ route('ventas.factura.store', $sale) }}">
            @csrf
            <button type="submit" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Emitir factura</button>
        </form>
    @endif
@endsection

@section('content')
    <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
        <dl class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <dt class="text-sm font-medium text-stone-500">Cliente</dt>
                <dd class="mt-1"><a href="{{ route('clientes.show', $sale->customer) }}" class="font-semibold text-brand-700 hover:text-brand-500">{{ $sale->customer->name }}</a></dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-stone-500">Pedido</dt>
                <dd class="mt-1">
                    @if ($sale->order)
                        <a href="{{ route('pedidos.show', $sale->order) }}" class="font-semibold text-brand-700 hover:text-brand-500">{{ $sale->order->number }}</a>
                    @else
                        Venta directa
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-stone-500">Fecha</dt>
                <dd class="mt-1">{{ $sale->sale_date->format('Y-m-d') }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-stone-500">Estado</dt>
                <dd class="mt-1 font-semibold">{{ $sale->status->label() }}</dd>
            </div>
            <div class="sm:col-span-2 lg:col-span-4">
                <dt class="text-sm font-medium text-stone-500">Notas</dt>
                <dd class="mt-1 whitespace-pre-line">{{ $sale->notes ?? 'Sin notas' }}</dd>
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
                        <th class="px-5 py-3 text-right">Precio unitario</th>
                        <th class="px-5 py-3 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @foreach ($sale->details as $detail)
                        <tr>
                            <td class="px-5 py-4">
                                <p class="font-semibold">{{ $detail->product->name }}</p>
                                <p class="mt-1 text-xs text-stone-500">{{ $detail->product->sku }}</p>
                            </td>
                            <td class="px-5 py-4 text-right tabular-nums">{{ $detail->quantity }}</td>
                            <td class="px-5 py-4 text-right tabular-nums">Q {{ number_format((float) $detail->unit_price, 2) }}</td>
                            <td class="px-5 py-4 text-right tabular-nums">Q {{ number_format((float) $detail->subtotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-stone-50">
                    <tr>
                        <th colspan="3" class="px-5 py-4 text-right text-base">Total</th>
                        <td class="px-5 py-4 text-right text-base font-bold tabular-nums">Q {{ number_format((float) $sale->total, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </section>

    <div>
        <a href="{{ route('ventas.index') }}" class="rounded-lg border border-stone-300 bg-white px-4 py-2 text-sm font-semibold hover:bg-stone-50">Volver a ventas</a>
    </div>
@endsection
