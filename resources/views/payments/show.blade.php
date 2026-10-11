@extends('layouts.admin')

@section('title', 'Pago '.$payment->number)
@section('heading', $payment->number)
@section('subheading', 'Pago de factura '.$payment->invoice->number)

@section('actions')
    <a href="{{ route('pagos.receipt', $payment) }}" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Ver e imprimir recibo</a>
@endsection

@section('content')
    <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
        <dl class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <div><dt class="text-sm font-medium text-stone-500">Número de recibo</dt><dd class="mt-1 font-semibold">{{ $payment->number }}</dd></div>
            <div><dt class="text-sm font-medium text-stone-500">Factura</dt><dd class="mt-1"><a href="{{ route('facturas.show', $payment->invoice) }}" class="font-semibold text-brand-700">{{ $payment->invoice->number }}</a></dd></div>
            <div><dt class="text-sm font-medium text-stone-500">Cliente</dt><dd class="mt-1">{{ $payment->invoice->sale->customer->name }}</dd></div>
            <div><dt class="text-sm font-medium text-stone-500">Fecha</dt><dd class="mt-1">{{ $payment->payment_date->format('Y-m-d') }}</dd></div>
            <div><dt class="text-sm font-medium text-stone-500">Método</dt><dd class="mt-1">{{ $payment->method->label() }}</dd></div>
            <div><dt class="text-sm font-medium text-stone-500">Monto</dt><dd class="mt-1 font-semibold tabular-nums">Q {{ number_format((float) $payment->amount, 2) }}</dd></div>
            <div class="sm:col-span-2 lg:col-span-3"><dt class="text-sm font-medium text-stone-500">Notas</dt><dd class="mt-1 whitespace-pre-line">{{ $payment->notes ?: 'Sin notas' }}</dd></div>
        </dl>
    </section>
    <div class="flex gap-3"><a href="{{ route('facturas.show', $payment->invoice) }}" class="rounded-lg border border-stone-300 bg-white px-4 py-2 text-sm font-semibold hover:bg-stone-50">Volver a factura</a><a href="{{ route('pagos.index') }}" class="rounded-lg border border-stone-300 bg-white px-4 py-2 text-sm font-semibold hover:bg-stone-50">Volver a pagos</a></div>
@endsection
