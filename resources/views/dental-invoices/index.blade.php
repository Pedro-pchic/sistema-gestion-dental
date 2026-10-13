@extends('layouts.admin')

@section('title', 'Facturación dental')
@section('heading', ($filters['balance'] ?? null) === 'pending' ? 'Cuentas pendientes' : 'Facturas dentales')
@section('subheading', ($filters['balance'] ?? null) === 'pending' ? 'Facturas emitidas con saldo por cobrar' : 'Comprobantes administrativos por procedimientos realizados')

@section('actions')
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('facturacion.facturas.index', ['balance' => 'pending']) }}" class="ui-button ui-button-secondary">Cuentas pendientes</a>
        <a href="{{ route('facturacion.facturas.create') }}" class="ui-button ui-button-primary">Crear factura</a>
    </div>
@endsection

@section('content')
    <form method="GET" action="{{ route('facturacion.facturas.index') }}" class="grid gap-4 ui-card p-5 sm:grid-cols-2 lg:grid-cols-3 lg:items-end">
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
        <label class="flex flex-col gap-1 text-sm font-medium text-stone-700">Desde
            <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="rounded-md border border-stone-300 px-3 py-2 font-normal">
        </label>
        <label class="flex flex-col gap-1 text-sm font-medium text-stone-700">Hasta
            <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="rounded-md border border-stone-300 px-3 py-2 font-normal">
        </label>
        <label class="flex flex-col gap-1 text-sm font-medium text-stone-700">Ordenar
            <select name="sort" class="rounded-md border border-stone-300 px-3 py-2 font-normal">
                @foreach (['newest' => 'Más recientes', 'oldest' => 'Más antiguas', 'number' => 'Número', 'total' => 'Mayor total', 'balance' => 'Mayor saldo'] as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['sort'] ?? 'newest') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="flex items-center gap-2 text-sm font-medium text-stone-700">
            <input type="checkbox" name="balance" value="pending" @checked(($filters['balance'] ?? null) === 'pending') class="rounded border-stone-300"> Solo saldos pendientes
        </label>
        <button type="submit" class="ui-button ui-button-secondary">Filtrar</button>
    </form>

    <section class="mt-6 ui-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[64rem] divide-y divide-stone-200 text-sm">
                <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">
                    <tr><th class="px-5 py-3">Número</th><th class="px-5 py-3">Paciente</th><th class="px-5 py-3">Fecha</th><th class="px-5 py-3">Estado</th><th class="px-5 py-3 text-right">Total</th><th class="px-5 py-3 text-right">Abonos</th><th class="px-5 py-3 text-right">Saldo</th><th class="px-5 py-3"></th></tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($invoices as $invoice)
                        <tr>
                            <td class="px-5 py-4 font-semibold">{{ $invoice->number ?? 'Borrador' }}</td>
                            <td class="px-5 py-4">{{ $invoice->patient->first_name }} {{ $invoice->patient->last_name }}</td>
                            <td class="px-5 py-4">{{ $invoice->issue_date?->format('d/m/Y') ?? 'Sin emitir' }}</td>
                            <td class="px-5 py-4"><span class="ui-badge" data-status="{{ $invoice->status->value }}">{{ $invoice->status->label() }}</span></td>
                            <td class="px-5 py-4 text-right tabular-nums">Q {{ number_format((float) $invoice->total, 2) }}</td>
                            <td class="px-5 py-4 text-right tabular-nums">Q {{ number_format((float) ($invoice->paid_amount ?? 0), 2) }}</td>
                            <td class="px-5 py-4 text-right tabular-nums">Q {{ number_format((float) $invoice->balance_due, 2) }}</td>
                            <td class="px-5 py-4 text-right"><a href="{{ route('facturacion.facturas.show', $invoice) }}" class="font-semibold text-brand-700 hover:text-brand-500">{{ match ($invoice->status) { \App\Enums\DentalInvoiceStatus::Draft => 'Revisar', \App\Enums\DentalInvoiceStatus::Issued, \App\Enums\DentalInvoiceStatus::PartiallyPaid => 'Ver / cobrar', default => 'Ver' } }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-5 py-8 text-center text-stone-500">No hay facturas que coincidan con la búsqueda.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <div class="mt-4">{{ $invoices->links() }}</div>
@endsection
