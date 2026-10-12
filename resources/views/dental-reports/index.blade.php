@extends('layouts.admin')

@section('title', 'Reportes financieros dentales')
@section('heading', 'Reportes financieros')
@section('subheading', 'Facturación y pagos del consultorio')

@section('content')
    <form method="GET" action="{{ route('facturacion.reportes.index') }}" class="grid gap-4 rounded-xl bg-white p-5 shadow-sm ring-1 ring-stone-200 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
        <label class="flex flex-col gap-1 text-sm font-medium">Desde
            <input type="date" name="from" value="{{ $from }}" required class="rounded-md border border-stone-300 px-3 py-2 font-normal">
        </label>
        <label class="flex flex-col gap-1 text-sm font-medium">Hasta
            <input type="date" name="to" value="{{ $to }}" required class="rounded-md border border-stone-300 px-3 py-2 font-normal">
        </label>
        <button type="submit" class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold hover:bg-stone-50">Actualizar</button>
    </form>

    <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-stone-200"><p class="text-sm text-stone-500">Facturas emitidas</p><p class="mt-2 text-2xl font-bold">{{ $issuedInvoices->count() }}</p><p class="mt-1 text-sm text-stone-600">Q {{ number_format($issuedInvoiceCents / 100, 2) }} en el período</p></article>
        <article class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-stone-200"><p class="text-sm text-stone-500">Pagos registrados</p><p class="mt-2 text-2xl font-bold">{{ $payments->count() }}</p><p class="mt-1 text-sm text-stone-600">Q {{ number_format($receivedCents / 100, 2) }} recibidos</p></article>
        <article class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-stone-200"><p class="text-sm text-stone-500">Facturas pendientes</p><p class="mt-2 text-2xl font-bold">{{ $pendingInvoices->count() }}</p><p class="mt-1 text-sm text-stone-600">Q {{ number_format($pendingCents / 100, 2) }} de saldo</p></article>
        <article class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-stone-200"><p class="text-sm text-stone-500">Período</p><p class="mt-2 text-lg font-bold">{{ \Illuminate\Support\Carbon::parse($from)->format('d/m/Y') }}</p><p class="mt-1 text-sm text-stone-600">al {{ \Illuminate\Support\Carbon::parse($to)->format('d/m/Y') }}</p></article>
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-2">
        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-stone-200">
            <div class="border-b border-stone-200 px-5 py-4"><h2 class="font-semibold">Saldos por paciente</h2><p class="mt-1 text-sm text-stone-500">Hasta 30 pacientes con saldo pendiente.</p></div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-stone-200 text-sm"><thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-500"><tr><th class="px-5 py-3">Paciente</th><th class="px-5 py-3 text-right">Saldo</th></tr></thead><tbody class="divide-y divide-stone-100">
                    @forelse ($pendingByPatient as $row)
                        <tr><td class="px-5 py-3">{{ $row->patient->first_name }} {{ $row->patient->last_name }} · {{ $row->patient->record_number }}</td><td class="px-5 py-3 text-right tabular-nums">Q {{ number_format($row->balance_cents / 100, 2) }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="px-5 py-7 text-center text-stone-500">No hay saldos pendientes en este período.</td></tr>
                    @endforelse
                </tbody></table>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-stone-200">
            <div class="border-b border-stone-200 px-5 py-4"><h2 class="font-semibold">Ingresos por día</h2><p class="mt-1 text-sm text-stone-500">Pagos registrados en el período.</p></div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-stone-200 text-sm"><thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-500"><tr><th class="px-5 py-3">Fecha</th><th class="px-5 py-3 text-right">Ingresos</th></tr></thead><tbody class="divide-y divide-stone-100">
                    @forelse ($revenueByDay as $row)
                        <tr><td class="px-5 py-3">{{ \Illuminate\Support\Carbon::parse($row->payment_date)->format('d/m/Y') }}</td><td class="px-5 py-3 text-right tabular-nums">Q {{ number_format($row->amount_cents / 100, 2) }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="px-5 py-7 text-center text-stone-500">No hay pagos registrados en este período.</td></tr>
                    @endforelse
                </tbody></table>
            </div>
        </div>
    </section>
@endsection
