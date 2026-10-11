@extends('layouts.admin')

@section('title', 'Orden '.$purchase->number)
@section('heading', 'Orden de compra')
@section('subheading', $purchase->number)

@section('actions')
    @can('manage-purchases')
        @if ($purchase->status === \App\Enums\PurchaseStatus::Draft)
            <a href="{{ route('compras.edit', $purchase) }}" class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold hover:bg-stone-50">Editar borrador</a>
            <form method="POST" action="{{ route('compras.submit', $purchase) }}">
                @csrf
                <button type="submit" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600">Enviar a pendiente</button>
            </form>
        @endif
        @if (in_array($purchase->status, [\App\Enums\PurchaseStatus::Draft, \App\Enums\PurchaseStatus::Pending], true))
            <form method="POST" action="{{ route('compras.cancel', $purchase) }}">
                @csrf
                <button type="submit" class="rounded-lg border border-red-200 px-4 py-2 text-sm font-semibold text-red-800 hover:bg-red-50">Cancelar orden</button>
            </form>
        @endif
    @endcan
    @can('receive-purchases')
        @if ($purchase->status === \App\Enums\PurchaseStatus::Pending)
            <form method="POST" action="{{ route('compras.receive', $purchase) }}">
                @csrf
                <button type="submit" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-600">Recibir mercadería</button>
            </form>
        @endif
    @endcan
@endsection

@section('content')
    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
        <dl class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <dt class="text-sm font-medium text-stone-500">Proveedor</dt>
                <dd class="mt-1 font-semibold">
                    @can('manage-purchases')
                        <a href="{{ route('proveedores.show', $purchase->supplier) }}" class="text-brand-700 hover:underline">{{ $purchase->supplier->name }}</a>
                    @else
                        {{ $purchase->supplier->name }}
                    @endcan
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-stone-500">Fecha de orden</dt>
                <dd class="mt-1">{{ $purchase->order_date->format('d/m/Y') }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-stone-500">Estado</dt>
                <dd class="mt-1 font-semibold">{{ $purchase->status->label() }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-stone-500">Recibida</dt>
                <dd class="mt-1">{{ $purchase->received_at?->format('d/m/Y H:i') ?? 'Pendiente' }}</dd>
            </div>
            @if ($purchase->notes)
                <div class="sm:col-span-2 lg:col-span-4">
                    <dt class="text-sm font-medium text-stone-500">Notas</dt>
                    <dd class="mt-1 whitespace-pre-line">{{ $purchase->notes }}</dd>
                </div>
            @endif
        </dl>
    </div>

    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-stone-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200 text-sm">
                <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-600">
                    <tr>
                        <th class="px-4 py-3">Producto</th>
                        <th class="px-4 py-3 text-right">Cantidad</th>
                        <th class="px-4 py-3 text-right">Costo unitario</th>
                        <th class="px-4 py-3 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @foreach ($purchase->details as $detail)
                        <tr>
                            <td class="px-4 py-3">
                                <span class="font-medium">{{ $detail->product->name }}</span>
                                <span class="mt-0.5 block font-mono text-xs text-stone-500">{{ $detail->product->sku }}</span>
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ number_format($detail->quantity) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ number_format((float) $detail->unit_cost, 2) }}</td>
                            <td class="px-4 py-3 text-right font-semibold tabular-nums">{{ number_format((float) $detail->subtotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-stone-50">
                    <tr>
                        <th colspan="3" class="px-4 py-3 text-right text-sm font-semibold">Total</th>
                        <td class="px-4 py-3 text-right text-base font-bold tabular-nums">{{ number_format((float) $purchase->total, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div>
        <a href="{{ route('compras.index') }}" class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold hover:bg-stone-50">Volver a órdenes</a>
    </div>
@endsection
