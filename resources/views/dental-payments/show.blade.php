@extends('layouts.admin')

@section('title', 'Pago '.$payment->number)
@section('heading', $payment->number)
@section('subheading', 'Recibo de la factura '.$payment->invoice->number)

@section('actions')
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('facturacion.pagos.receipt', $payment) }}" class="ui-button ui-button-primary">Recibo imprimible</a>
        @can('cancel', $payment)
            @if ($payment->status === \App\Enums\DentalPaymentStatus::Registered)
                <form method="POST" action="{{ route('facturacion.pagos.cancel', $payment) }}" class="flex flex-wrap gap-2">
                    @csrf
                    <label for="payment-cancel-reason" class="sr-only">Motivo de anulación del pago</label>
                    <input id="payment-cancel-reason" name="reason" required minlength="5" maxlength="1000" placeholder="Motivo de anulación" class="w-52 rounded-md border border-stone-300 px-3 py-2 text-sm">
                    <button type="submit" class="ui-button ui-button-danger">Anular pago</button>
                </form>
            @endif
        @endcan
    </div>
@endsection

@section('content')
    <section class="ui-card p-5 sm:p-6">
        <dl class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <div><dt class="text-sm font-medium text-stone-500">Número de recibo</dt><dd class="mt-1 font-semibold">{{ $payment->number }}</dd></div>
            <div><dt class="text-sm font-medium text-stone-500">Factura</dt><dd class="mt-1"><a href="{{ route('facturacion.facturas.show', $payment->invoice) }}" class="font-semibold text-brand-700">{{ $payment->invoice->number }}</a></dd></div>
            <div><dt class="text-sm font-medium text-stone-500">Paciente</dt><dd class="mt-1">{{ $payment->invoice->patient->first_name }} {{ $payment->invoice->patient->last_name }}</dd></div>
            <div><dt class="text-sm font-medium text-stone-500">Fecha</dt><dd class="mt-1">{{ $payment->payment_date->format('d/m/Y') }}</dd></div>
            <div><dt class="text-sm font-medium text-stone-500">Método</dt><dd class="mt-1">{{ $payment->method->label() }}</dd></div>
            <div><dt class="text-sm font-medium text-stone-500">Estado</dt><dd class="mt-1"><span class="ui-badge" data-status="{{ $payment->status->value }}">{{ $payment->status->label() }}</span></dd></div>
            <div><dt class="text-sm font-medium text-stone-500">Monto</dt><dd class="mt-1 font-semibold tabular-nums">Q {{ number_format((float) $payment->amount, 2) }}</dd></div>
            <div><dt class="text-sm font-medium text-stone-500">Referencia</dt><dd class="mt-1">{{ $payment->reference ?: 'Sin referencia' }}</dd></div>
            <div><dt class="text-sm font-medium text-stone-500">Registrado por</dt><dd class="mt-1">{{ $payment->recorder->name }}</dd></div>
            @if ($payment->status === \App\Enums\DentalPaymentStatus::Cancelled)
                <div><dt class="text-sm font-medium text-stone-500">Anulado por</dt><dd class="mt-1">{{ $payment->canceller?->name ?? 'Usuario no disponible' }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-sm font-medium text-stone-500">Motivo</dt><dd class="mt-1 whitespace-pre-line">{{ $payment->cancellation_reason }}</dd></div>
            @endif
            @if ($payment->notes)
                <div class="sm:col-span-2 lg:col-span-3"><dt class="text-sm font-medium text-stone-500">Notas administrativas</dt><dd class="mt-1 whitespace-pre-line">{{ $payment->notes }}</dd></div>
            @endif
        </dl>
    </section>
    <div class="mt-5 flex flex-wrap gap-3"><a href="{{ route('facturacion.facturas.show', $payment->invoice) }}" class="ui-button ui-button-secondary">Volver a factura</a><a href="{{ route('facturacion.pagos.index') }}" class="ui-button ui-button-secondary">Volver a pagos</a></div>
@endsection
