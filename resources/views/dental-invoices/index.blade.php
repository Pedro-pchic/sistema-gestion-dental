@extends('layouts.admin')

@section('title', 'Facturación dental')
@section('heading', 'Facturas dentales')
@section('subheading', 'Comprobantes administrativos por procedimientos realizados')

@section('actions')
    <a href="{{ route('facturacion.facturas.create') }}" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600">Crear factura</a>
@endsection

@section('content')
    <form method="GET" action="{{ route('facturacion.facturas.index') }}" class="grid gap-4 rounded-xl bg-white p-5 shadow-sm ring-1 ring-stone-200 sm:grid-cols-[1fr_14rem_auto] sm:items-end">
        <label class="flex flex-col gap-1 text-sm font-medium text-stone-700">Buscar paciente o número
            <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" class="rounded-md border border-stone-300 px-3 py-2 font-normal" placeholder="Nombre o DFA-...">
        </label>
        <label class="flex flex-col gap-1 text-sm font-medium text-stone-700">Estado
            <select name="status" class="rounded-md border border-stone-300 px-3 py-2 font-normal">
                <option value="">Todos</option>
                @foreach (\App\Enums\DentalInvoiceStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </label>
        <button type="submit" class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold hover:bg-stone-50">Filtrar</button>
    </form>

    <section class="mt-6 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-stone-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200 text-sm">
                <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">
                    <tr><th class="px-5 py-3">Número</th><th class="px-5 py-3">Paciente</th><th class="px-5 py-3">Fecha</th><th class="px-5 py-3">Estado</th><th class="px-5 py-3 text-right">Total</th><th class="px-5 py-3 text-right">Saldo</th><th class="px-5 py-3"></th></tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($invoices as $invoice)
                        <tr>
                            <td class="px-5 py-4 font-semibold">{{ $invoice->number ?? 'Borrador' }}</td>
                            <td class="px-5 py-4">{{ $invoice->patient->first_name }} {{ $invoice->patient->last_name }}</td>
                            <td class="px-5 py-4">{{ $invoice->issue_date?->format('d/m/Y') ?? 'Sin emitir' }}</td>
                            <td class="px-5 py-4">{{ $invoice->status->label() }}</td>
                            <td class="px-5 py-4 text-right tabular-nums">Q {{ number_format((float) $invoice->total, 2) }}</td>
                            <td class="px-5 py-4 text-right tabular-nums">Q {{ number_format((float) $invoice->balance_due, 2) }}</td>
                            <td class="px-5 py-4 text-right"><a href="{{ route('facturacion.facturas.show', $invoice) }}" class="font-semibold text-brand-700 hover:text-brand-500">Ver</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-8 text-center text-stone-500">No hay facturas que coincidan con la búsqueda.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <div class="mt-4">{{ $invoices->links() }}</div>
@endsection
