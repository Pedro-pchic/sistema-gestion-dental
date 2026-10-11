@extends('layouts.admin')

@section('title', 'Detalle de cotización')
@section('heading', $quote->number)
@section('subheading', 'Cotización para '.$quote->customer->name)

@section('actions')
    <div class="flex flex-wrap gap-3">
        @if ($quote->status === \App\Enums\QuoteStatus::Draft)
            <a href="{{ route('cotizaciones.edit', $quote) }}" class="rounded-lg border border-stone-300 bg-white px-4 py-2 text-sm font-semibold hover:bg-stone-50">Editar borrador</a>
            <form method="POST" action="{{ route('cotizaciones.send', $quote) }}">
                @csrf
                <button type="submit" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600">Marcar como enviada</button>
            </form>
            <form method="POST" action="{{ route('cotizaciones.reject', $quote) }}">
                @csrf
                <button type="submit" class="rounded-lg border border-red-200 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">Rechazar</button>
            </form>
        @elseif ($quote->status === \App\Enums\QuoteStatus::Sent)
            <form method="POST" action="{{ route('cotizaciones.accept', $quote) }}">
                @csrf
                <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Aceptar</button>
            </form>
            <form method="POST" action="{{ route('cotizaciones.reject', $quote) }}">
                @csrf
                <button type="submit" class="rounded-lg border border-red-200 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">Rechazar</button>
            </form>
        @endif
        @if ($quote->status === \App\Enums\QuoteStatus::Accepted)
            @if ($quote->order)
                <a href="{{ route('pedidos.show', $quote->order) }}" class="rounded-lg border border-stone-300 bg-white px-4 py-2 text-sm font-semibold hover:bg-stone-50">Ver pedido {{ $quote->order->number }}</a>
            @else
                <form method="POST" action="{{ route('cotizaciones.pedido.store', $quote) }}">
                    @csrf
                    <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Convertir en pedido</button>
                </form>
            @endif
        @endif
    </div>
@endsection

@section('content')
    <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
        <dl class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <dt class="text-sm font-medium text-stone-500">Cliente</dt>
                <dd class="mt-1"><a href="{{ route('clientes.show', $quote->customer) }}" class="font-semibold text-brand-700 hover:text-brand-500">{{ $quote->customer->name }}</a></dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-stone-500">Fecha</dt>
                <dd class="mt-1">{{ $quote->quote_date->format('Y-m-d') }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-stone-500">Vigencia</dt>
                <dd class="mt-1">{{ $quote->valid_until?->format('Y-m-d') ?? 'Sin fecha de vencimiento' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-stone-500">Estado</dt>
                <dd class="mt-1 font-semibold">{{ $quote->status->label() }}</dd>
            </div>
            <div class="sm:col-span-2 lg:col-span-4">
                <dt class="text-sm font-medium text-stone-500">Notas</dt>
                <dd class="mt-1 whitespace-pre-line">{{ $quote->notes ?? 'Sin notas' }}</dd>
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
                    @foreach ($quote->details as $detail)
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
                        <td class="px-5 py-4 text-right text-base font-bold tabular-nums">Q {{ number_format((float) $quote->total, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </section>

    <div>
        <a href="{{ route('cotizaciones.index') }}" class="rounded-lg border border-stone-300 bg-white px-4 py-2 text-sm font-semibold hover:bg-stone-50">Volver a cotizaciones</a>
    </div>
@endsection
