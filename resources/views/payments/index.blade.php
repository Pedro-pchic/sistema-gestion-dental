@extends('layouts.admin')

@section('title', 'Pagos')
@section('heading', 'Pagos')
@section('subheading', 'Pagos registrados en facturas')

@section('content')
    <section class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-stone-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200 text-sm">
                <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">
                    <tr><th class="px-5 py-3">Recibo</th><th class="px-5 py-3">Factura</th><th class="px-5 py-3">Cliente</th><th class="px-5 py-3">Fecha</th><th class="px-5 py-3">Método</th><th class="px-5 py-3 text-right">Monto</th><th class="px-5 py-3"></th></tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($payments as $payment)
                        <tr>
                            <td class="px-5 py-4 font-semibold">{{ $payment->number }}</td>
                            <td class="px-5 py-4">{{ $payment->invoice->number }}</td>
                            <td class="px-5 py-4">{{ $payment->invoice->sale->customer->name }}</td>
                            <td class="px-5 py-4">{{ $payment->payment_date->format('Y-m-d') }}</td>
                            <td class="px-5 py-4">{{ $payment->method->label() }}</td>
                            <td class="px-5 py-4 text-right tabular-nums">Q {{ number_format((float) $payment->amount, 2) }}</td>
                            <td class="px-5 py-4 text-right"><a href="{{ route('pagos.show', $payment) }}" class="font-semibold text-brand-700 hover:text-brand-500">Ver</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-8 text-center text-stone-500">Todavía no hay pagos registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    {{ $payments->links() }}
@endsection
