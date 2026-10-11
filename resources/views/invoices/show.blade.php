@extends('layouts.admin')

@section('title', 'Factura '.$invoice->number)
@section('heading', $invoice->number)
@section('subheading', 'Factura de '.$invoice->sale->customer->name)

@section('actions')
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('facturas.print', $invoice) }}" class="rounded-lg border border-stone-300 bg-white px-4 py-2 text-sm font-semibold hover:bg-stone-50">Imprimir</a>
        @can('manage-finances')
            @if ($invoice->status->value !== 'cancelled' && $invoice->payments->isEmpty() && ! $invoice->creditNotes->contains(fn ($creditNote) => $creditNote->status->value === 'issued'))
                <form method="POST" action="{{ route('facturas.cancel', $invoice) }}">
                    @csrf
                    <button type="submit" class="rounded-lg border border-red-300 bg-white px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">Cancelar factura</button>
                </form>
            @endif
        @endcan
    </div>
@endsection

@section('content')
    <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
        <dl class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <div><dt class="text-sm font-medium text-stone-500">Cliente</dt><dd class="mt-1 font-semibold">{{ $invoice->sale->customer->name }}</dd></div>
            <div><dt class="text-sm font-medium text-stone-500">Venta</dt><dd class="mt-1">
                @can('manage-commercial')
                    <a class="font-semibold text-brand-700" href="{{ route('ventas.show', $invoice->sale) }}">{{ $invoice->sale->number }}</a>
                @else
                    {{ $invoice->sale->number }}
                @endcan
            </dd></div>
            <div><dt class="text-sm font-medium text-stone-500">Fecha de emisión</dt><dd class="mt-1">{{ $invoice->issue_date->format('Y-m-d') }}</dd></div>
            <div><dt class="text-sm font-medium text-stone-500">Estado</dt><dd class="mt-1 font-semibold">{{ $invoice->status->label() }}</dd></div>
            <div><dt class="text-sm font-medium text-stone-500">Saldo</dt><dd class="mt-1 font-semibold tabular-nums">Q {{ number_format((float) $balanceDue, 2) }}</dd></div>
            <div><dt class="text-sm font-medium text-stone-500">Notas</dt><dd class="mt-1 whitespace-pre-line">{{ $invoice->notes ?: 'Sin notas' }}</dd></div>
        </dl>
    </section>

    <section class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-stone-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200 text-sm">
                <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">
                    <tr><th class="px-5 py-3">Producto</th><th class="px-5 py-3 text-right">Cantidad</th><th class="px-5 py-3 text-right">Precio unitario</th><th class="px-5 py-3 text-right">Subtotal</th></tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @foreach ($invoice->sale->details as $detail)
                        <tr>
                            <td class="px-5 py-4"><p class="font-semibold">{{ $detail->product->name }}</p><p class="mt-1 text-xs text-stone-500">{{ $detail->product->sku }}</p></td>
                            <td class="px-5 py-4 text-right tabular-nums">{{ $detail->quantity }}</td>
                            <td class="px-5 py-4 text-right tabular-nums">Q {{ number_format((float) $detail->unit_price, 2) }}</td>
                            <td class="px-5 py-4 text-right tabular-nums">Q {{ number_format((float) $detail->subtotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-stone-50"><tr><th colspan="3" class="px-5 py-4 text-right">Total</th><td class="px-5 py-4 text-right font-bold tabular-nums">Q {{ number_format((float) $invoice->total, 2) }}</td></tr></tfoot>
            </table>
        </div>
    </section>

    <section class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
            <h2 class="text-lg font-semibold">Historial de pagos</h2>
            @if ($invoice->payments->isEmpty())
                <p class="mt-3 text-sm text-stone-600">No hay pagos registrados.</p>
            @else
                <ul class="mt-3 divide-y divide-stone-100">
                    @foreach ($invoice->payments as $payment)
                        <li class="flex flex-wrap items-center justify-between gap-2 py-3 text-sm">
                            @can('manage-finances')
                                <a href="{{ route('pagos.show', $payment) }}" class="font-semibold text-brand-700">{{ $payment->number }}</a>
                            @else
                                <span class="font-semibold">{{ $payment->number }}</span>
                            @endcan
                            <span>{{ $payment->payment_date->format('Y-m-d') }}</span>
                            <span class="tabular-nums">Q {{ number_format((float) $payment->amount, 2) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
            <h2 class="text-lg font-semibold">Notas de crédito</h2>
            @if ($invoice->creditNotes->isEmpty())
                <p class="mt-3 text-sm text-stone-600">No hay notas de crédito.</p>
            @else
                <ul class="mt-3 divide-y divide-stone-100">
                    @foreach ($invoice->creditNotes as $creditNote)
                        <li class="flex flex-wrap items-center justify-between gap-2 py-3 text-sm">
                            @can('manage-finances')
                                <a href="{{ route('notas-credito.show', $creditNote) }}" class="font-semibold text-brand-700">{{ $creditNote->number }}</a>
                            @else
                                <span class="font-semibold">{{ $creditNote->number }}</span>
                            @endcan
                            <span>{{ $creditNote->status->label() }}</span>
                            <span class="tabular-nums">Q {{ number_format((float) $creditNote->amount, 2) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>

    @can('manage-finances')
        @if ($invoice->status->value !== 'cancelled' && (float) $balanceDue > 0)
            <section class="grid gap-6 lg:grid-cols-2">
                <form method="POST" action="{{ route('facturas.pagos.store', $invoice) }}" class="flex flex-col gap-4 rounded-xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
                    @csrf
                    <div><h2 class="text-lg font-semibold">Registrar pago</h2><p class="mt-1 text-sm text-stone-600">Saldo disponible: Q {{ number_format((float) $balanceDue, 2) }}</p></div>
                    <label class="flex flex-col gap-1 text-sm font-medium">Monto
                        <input name="amount" type="number" min="0.01" max="{{ $balanceDue }}" step="0.01" value="{{ old('amount') }}" required class="rounded-md border border-stone-300 px-3 py-2 font-normal">
                    </label>
                    <label class="flex flex-col gap-1 text-sm font-medium">Método
                        <select name="method" required class="rounded-md border border-stone-300 px-3 py-2 font-normal">
                            @foreach (\App\Enums\PaymentMethod::cases() as $method)
                                <option value="{{ $method->value }}" @selected(old('method') === $method->value)>{{ $method->label() }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="flex flex-col gap-1 text-sm font-medium">Fecha
                        <input name="payment_date" type="date" value="{{ old('payment_date', now()->format('Y-m-d')) }}" required class="rounded-md border border-stone-300 px-3 py-2 font-normal">
                    </label>
                    <label class="flex flex-col gap-1 text-sm font-medium">Notas
                        <textarea name="notes" rows="2" class="rounded-md border border-stone-300 px-3 py-2 font-normal">{{ old('notes') }}</textarea>
                    </label>
                    <button type="submit" class="self-start rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Guardar pago</button>
                </form>

                <form method="POST" action="{{ route('facturas.notas-credito.store', $invoice) }}" class="flex flex-col gap-4 rounded-xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
                    @csrf
                    <div><h2 class="text-lg font-semibold">Emitir nota de crédito</h2><p class="mt-1 text-sm text-stone-600">El monto se aplicará al saldo pendiente.</p></div>
                    <label class="flex flex-col gap-1 text-sm font-medium">Monto
                        <input name="amount" type="number" min="0.01" max="{{ $balanceDue }}" step="0.01" value="{{ old('amount') }}" required class="rounded-md border border-stone-300 px-3 py-2 font-normal">
                    </label>
                    <label class="flex flex-col gap-1 text-sm font-medium">Fecha
                        <input name="issue_date" type="date" value="{{ old('issue_date', now()->format('Y-m-d')) }}" required class="rounded-md border border-stone-300 px-3 py-2 font-normal">
                    </label>
                    <label class="flex flex-col gap-1 text-sm font-medium">Motivo
                        <textarea name="reason" rows="3" required class="rounded-md border border-stone-300 px-3 py-2 font-normal">{{ old('reason') }}</textarea>
                    </label>
                    <button type="submit" class="self-start rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Emitir nota</button>
                </form>
            </section>
        @endif
    @endcan

    <div><a href="{{ route('facturas.index') }}" class="rounded-lg border border-stone-300 bg-white px-4 py-2 text-sm font-semibold hover:bg-stone-50">Volver a facturas</a></div>
@endsection
