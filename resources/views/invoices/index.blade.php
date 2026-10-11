@extends('layouts.admin')

@section('title', 'Facturas')
@section('heading', 'Facturas')
@section('subheading', 'Facturas emitidas desde ventas confirmadas')

@section('content')
    <section class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-stone-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200 text-sm">
                <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">
                    <tr>
                        <th class="px-5 py-3">Factura</th>
                        <th class="px-5 py-3">Cliente</th>
                        <th class="px-5 py-3">Venta</th>
                        <th class="px-5 py-3">Emisión</th>
                        <th class="px-5 py-3">Estado</th>
                        <th class="px-5 py-3 text-right">Total</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($invoices as $invoice)
                        <tr>
                            <td class="px-5 py-4 font-semibold">{{ $invoice->number }}</td>
                            <td class="px-5 py-4">{{ $invoice->sale->customer->name }}</td>
                            <td class="px-5 py-4">{{ $invoice->sale->number }}</td>
                            <td class="px-5 py-4">{{ $invoice->issue_date->format('Y-m-d') }}</td>
                            <td class="px-5 py-4">{{ $invoice->status->label() }}</td>
                            <td class="px-5 py-4 text-right tabular-nums">Q {{ number_format((float) $invoice->total, 2) }}</td>
                            <td class="px-5 py-4 text-right"><a href="{{ route('facturas.show', $invoice) }}" class="font-semibold text-brand-700 hover:text-brand-500">Ver</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-8 text-center text-stone-500">Todavía no hay facturas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    {{ $invoices->links() }}
@endsection
