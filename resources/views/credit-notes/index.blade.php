@extends('layouts.admin')

@section('title', 'Notas de crédito')
@section('heading', 'Notas de crédito')
@section('subheading', 'Ajustes documentales aplicados a facturas')

@section('content')
    <section class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-stone-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200 text-sm">
                <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">
                    <tr><th class="px-5 py-3">Nota</th><th class="px-5 py-3">Factura</th><th class="px-5 py-3">Cliente</th><th class="px-5 py-3">Fecha</th><th class="px-5 py-3">Estado</th><th class="px-5 py-3 text-right">Monto</th><th class="px-5 py-3"></th></tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($creditNotes as $creditNote)
                        <tr>
                            <td class="px-5 py-4 font-semibold">{{ $creditNote->number }}</td>
                            <td class="px-5 py-4">{{ $creditNote->invoice->number }}</td>
                            <td class="px-5 py-4">{{ $creditNote->invoice->sale->customer->name }}</td>
                            <td class="px-5 py-4">{{ $creditNote->issue_date->format('Y-m-d') }}</td>
                            <td class="px-5 py-4">{{ $creditNote->status->label() }}</td>
                            <td class="px-5 py-4 text-right tabular-nums">Q {{ number_format((float) $creditNote->amount, 2) }}</td>
                            <td class="px-5 py-4 text-right"><a href="{{ route('notas-credito.show', $creditNote) }}" class="font-semibold text-brand-700 hover:text-brand-500">Ver</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-8 text-center text-stone-500">Todavía no hay notas de crédito.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    {{ $creditNotes->links() }}
@endsection
