@extends('layouts.admin')

@section('title', $invoice->number ?? 'Borrador de factura')
@section('heading', $invoice->number ?? 'Borrador de factura')
@section('subheading', 'Paciente: '.$invoice->patient->first_name.' '.$invoice->patient->last_name)

@section('actions')
    <div class="flex flex-wrap gap-2">
        @if ($invoice->number)
            <a href="{{ route('facturacion.facturas.document', $invoice) }}" class="ui-button ui-button-secondary">Comprobante imprimible</a>
        @endif
        @can('cancel', $invoice)
            @if ($invoice->status !== \App\Enums\DentalInvoiceStatus::Cancelled)
                <form method="POST" action="{{ route('facturacion.facturas.cancel', $invoice) }}" class="flex flex-wrap items-center gap-2">
                    @csrf
                    <label for="invoice-cancel-reason" class="sr-only">Motivo de anulación de la factura</label>
                    <input id="invoice-cancel-reason" name="reason" required minlength="5" maxlength="1000" placeholder="Motivo de anulación" class="w-52 rounded-md border border-stone-300 px-3 py-2 text-sm">
                    <button type="submit" class="ui-button ui-button-danger">Anular factura</button>
                </form>
            @endif
        @endcan
    </div>
@endsection

@section('content')
    <section class="ui-card p-5 sm:p-6">
        <dl class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <div><dt class="text-sm font-medium text-stone-500">Paciente</dt><dd class="mt-1 font-semibold">{{ $invoice->patient->first_name }} {{ $invoice->patient->last_name }}</dd></div>
            <div><dt class="text-sm font-medium text-stone-500">Código de ficha</dt><dd class="mt-1">{{ $invoice->patient->record_number }}</dd></div>
            <div><dt class="text-sm font-medium text-stone-500">Fecha de emisión</dt><dd class="mt-1">{{ $invoice->issue_date?->format('d/m/Y') ?? 'Sin emitir' }}</dd></div>
            <div><dt class="text-sm font-medium text-stone-500">Estado</dt><dd class="mt-1"><span class="ui-badge" data-status="{{ $invoice->status->value }}">{{ $invoice->status->label() }}</span></dd></div>
            <div><dt class="text-sm font-medium text-stone-500">Creada por</dt><dd class="mt-1">{{ $invoice->creator->name }}</dd></div>
            @if ($invoice->issuer)
                <div><dt class="text-sm font-medium text-stone-500">Emitida por</dt><dd class="mt-1">{{ $invoice->issuer->name }}</dd></div>
            @endif
            @if ($invoice->status === \App\Enums\DentalInvoiceStatus::Cancelled)
                <div class="sm:col-span-2"><dt class="text-sm font-medium text-stone-500">Motivo de anulación</dt><dd class="mt-1 whitespace-pre-line">{{ $invoice->cancellation_reason }}</dd></div>
            @endif
        </dl>
    </section>

    <section class="mt-6 ui-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[48rem] divide-y divide-stone-200 text-sm">
                <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-500"><tr><th class="px-5 py-3">Procedimiento realizado</th><th class="px-5 py-3 text-right">Cantidad</th><th class="px-5 py-3 text-right">Precio unitario</th><th class="px-5 py-3 text-right">Subtotal</th></tr></thead>
                <tbody class="divide-y divide-stone-100">
                    @foreach ($invoice->details as $detail)
                        <tr><td class="px-5 py-4">{{ $detail->description }}</td><td class="px-5 py-4 text-right tabular-nums">{{ $detail->quantity }}</td><td class="px-5 py-4 text-right tabular-nums">Q {{ number_format((float) $detail->unit_price, 2) }}</td><td class="px-5 py-4 text-right tabular-nums">Q {{ number_format((float) $detail->subtotal, 2) }}</td></tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-stone-50 text-sm">
                    <tr><th colspan="3" class="px-5 pt-4 text-right font-medium">Subtotal</th><td class="px-5 pt-4 text-right tabular-nums">Q {{ number_format((float) $invoice->subtotal, 2) }}</td></tr>
                    <tr><th colspan="3" class="px-5 pt-2 text-right font-medium">Descuento</th><td class="px-5 pt-2 text-right tabular-nums">Q {{ number_format((float) $invoice->discount, 2) }}</td></tr>
                    <tr><th colspan="3" class="px-5 pt-2 text-right font-medium">Impuestos</th><td class="px-5 pt-2 text-right tabular-nums">Q {{ number_format((float) $invoice->tax_amount, 2) }}</td></tr>
                    <tr><th colspan="3" class="px-5 pt-3 text-right font-bold">Total</th><td class="px-5 pt-3 text-right font-bold tabular-nums">Q {{ number_format((float) $invoice->total, 2) }}</td></tr>
                    <tr><th colspan="3" class="px-5 pb-4 pt-2 text-right font-semibold">Saldo pendiente</th><td class="px-5 pb-4 pt-2 text-right font-semibold tabular-nums">Q {{ number_format((float) $invoice->balance_due, 2) }}</td></tr>
                </tfoot>
            </table>
        </div>
    </section>

    @can('recordPayment', $invoice)
        @if (in_array($invoice->status, [\App\Enums\DentalInvoiceStatus::Issued, \App\Enums\DentalInvoiceStatus::PartiallyPaid], true) && (float) $invoice->balance_due > 0)
            <form method="POST" action="{{ route('facturacion.pagos.store', $invoice) }}" class="mt-6 grid gap-4 ui-card p-5 sm:p-6 sm:grid-cols-2 lg:grid-cols-3">
                @csrf
                <input type="hidden" name="idempotency_key" value="{{ $paymentRequestKey }}">
                <div class="sm:col-span-2 lg:col-span-3"><h2 class="text-lg font-semibold">Registrar pago</h2><p class="mt-1 text-sm text-stone-600">Saldo disponible: Q {{ number_format((float) $invoice->balance_due, 2) }}</p></div>
                <label class="flex flex-col gap-1 text-sm font-medium">Monto
                    <input name="amount" type="number" min="0.01" max="{{ $invoice->balance_due }}" step="0.01" value="{{ old('amount') }}" required @error('amount') aria-invalid="true" aria-describedby="payment-amount-error" @enderror class="rounded-md border border-stone-300 px-3 py-2 font-normal">
                </label>
                <label class="flex flex-col gap-1 text-sm font-medium">Método
                    <select name="method" required class="rounded-md border border-stone-300 px-3 py-2 font-normal">
                        @foreach (\App\Enums\PaymentMethod::cases() as $method)
                            <option value="{{ $method->value }}" @selected(old('method') === $method->value)>{{ $method->label() }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="flex flex-col gap-1 text-sm font-medium">Fecha
                    <input name="payment_date" type="date" value="{{ old('payment_date', today()->toDateString()) }}" required class="rounded-md border border-stone-300 px-3 py-2 font-normal">
                </label>
                <label class="flex flex-col gap-1 text-sm font-medium">Referencia
                    <input name="reference" maxlength="120" value="{{ old('reference') }}" class="rounded-md border border-stone-300 px-3 py-2 font-normal">
                </label>
                <label class="flex flex-col gap-1 text-sm font-medium sm:col-span-2">Notas administrativas
                    <input name="notes" maxlength="2000" value="{{ old('notes') }}" class="rounded-md border border-stone-300 px-3 py-2 font-normal">
                </label>
                <div class="sm:col-span-2 lg:col-span-3">@error('amount')<p id="payment-amount-error" class="text-sm text-red-700">{{ $message }}</p>@enderror @error('invoice')<p class="text-sm text-red-700">{{ $message }}</p>@enderror</div>
                <button type="submit" class="self-start ui-button ui-button-primary">Guardar pago</button>
            </form>
        @endif
    @endcan

    @if ($invoice->status === \App\Enums\DentalInvoiceStatus::Draft)
        @can('issue', $invoice)
            <form method="POST" action="{{ route('facturacion.facturas.issue', $invoice) }}" class="mt-6 rounded-xl bg-brand-50 p-5 ring-1 ring-brand-200">
                @csrf
                <p class="text-sm text-brand-900">Al emitir se asignará el número y se fijarán los importes de este comprobante interno.</p>
                <button type="submit" class="mt-4 ui-button ui-button-primary">Emitir factura</button>
            </form>
        @endcan
    @endif

    <section class="mt-6 ui-card p-5 sm:p-6">
        <h2 class="text-lg font-semibold">Historial de pagos</h2>
        @if ($invoice->payments->isEmpty())
            <p class="mt-3 text-sm text-stone-600">No hay pagos registrados.</p>
        @else
            <ul class="mt-3 divide-y divide-stone-100">
                @foreach ($invoice->payments as $payment)
                    <li class="flex flex-wrap items-center justify-between gap-3 py-3 text-sm">
                        <a href="{{ route('facturacion.pagos.show', $payment) }}" class="font-semibold text-brand-700">{{ $payment->number }}</a>
                        <span>{{ $payment->payment_date->format('d/m/Y') }} · {{ $payment->method->label() }}</span>
                        <span class="ui-badge" data-status="{{ $payment->status->value }}">{{ $payment->status->label() }}</span>
                        <span class="tabular-nums">Q {{ number_format((float) $payment->amount, 2) }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <div class="mt-6 flex flex-wrap gap-3">
        <a href="{{ route('facturacion.facturas.index') }}" class="ui-button ui-button-secondary">Volver a facturas</a>
        <a href="{{ route('pacientes.resumen-financiero', $invoice->patient) }}" class="ui-button ui-button-secondary">Resumen del paciente</a>
    </div>
@endsection
