@extends('layouts.admin')

@section('title', 'Nota de crédito '.$creditNote->number)
@section('heading', $creditNote->number)
@section('subheading', 'Nota de crédito de factura '.$creditNote->invoice->number)

@section('actions')
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('notas-credito.print', $creditNote) }}" class="rounded-lg border border-stone-300 bg-white px-4 py-2 text-sm font-semibold hover:bg-stone-50">Imprimir</a>
        @if ($creditNote->status->value === 'issued')
            <form method="POST" action="{{ route('notas-credito.cancel', $creditNote) }}">
                @csrf
                <button type="submit" class="rounded-lg border border-red-300 bg-white px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">Cancelar nota</button>
            </form>
        @endif
    </div>
@endsection

@section('content')
    <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
        <dl class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <div><dt class="text-sm font-medium text-stone-500">Cliente</dt><dd class="mt-1">{{ $creditNote->invoice->sale->customer->name }}</dd></div>
            <div><dt class="text-sm font-medium text-stone-500">Factura</dt><dd class="mt-1"><a href="{{ route('facturas.show', $creditNote->invoice) }}" class="font-semibold text-brand-700">{{ $creditNote->invoice->number }}</a></dd></div>
            <div><dt class="text-sm font-medium text-stone-500">Fecha</dt><dd class="mt-1">{{ $creditNote->issue_date->format('Y-m-d') }}</dd></div>
            <div><dt class="text-sm font-medium text-stone-500">Estado</dt><dd class="mt-1">{{ $creditNote->status->label() }}</dd></div>
            <div><dt class="text-sm font-medium text-stone-500">Monto</dt><dd class="mt-1 font-semibold tabular-nums">Q {{ number_format((float) $creditNote->amount, 2) }}</dd></div>
            <div class="sm:col-span-2 lg:col-span-3"><dt class="text-sm font-medium text-stone-500">Motivo</dt><dd class="mt-1 whitespace-pre-line">{{ $creditNote->reason }}</dd></div>
        </dl>
    </section>
    <div class="flex gap-3"><a href="{{ route('facturas.show', $creditNote->invoice) }}" class="rounded-lg border border-stone-300 bg-white px-4 py-2 text-sm font-semibold hover:bg-stone-50">Volver a factura</a><a href="{{ route('notas-credito.index') }}" class="rounded-lg border border-stone-300 bg-white px-4 py-2 text-sm font-semibold hover:bg-stone-50">Volver a notas</a></div>
@endsection
