@extends('layouts.admin')

@section('title', 'Resumen financiero')
@section('heading', 'Resumen financiero')
@section('subheading', 'Ingresos, gastos y balance calculados por período')

@section('content')
    <form method="GET" action="{{ route('finanzas.index') }}" class="grid gap-4 rounded-xl bg-white p-5 shadow-sm ring-1 ring-stone-200 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
        <div>
            <label for="from" class="block text-sm font-medium">Desde</label>
            <input id="from" name="from" type="date" value="{{ $from }}" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
        </div>
        <div>
            <label for="to" class="block text-sm font-medium">Hasta</label>
            <input id="to" name="to" type="date" value="{{ $to }}" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
        </div>
        <button type="submit" class="rounded-lg bg-brand-700 px-4 py-2 font-semibold text-white hover:bg-brand-600">Filtrar</button>
    </form>

    <div class="grid gap-5 sm:grid-cols-3">
        <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-stone-200">
            <p class="text-sm font-medium text-stone-500">Ingresos</p>
            <p class="mt-2 text-2xl font-bold text-emerald-700 tabular-nums">Q {{ number_format((float) $incomeTotal, 2) }}</p>
        </section>
        <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-stone-200">
            <p class="text-sm font-medium text-stone-500">Gastos</p>
            <p class="mt-2 text-2xl font-bold text-red-700 tabular-nums">Q {{ number_format((float) $expenseTotal, 2) }}</p>
        </section>
        <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-stone-200">
            <p class="text-sm font-medium text-stone-500">Balance</p>
            <p class="mt-2 text-2xl font-bold tabular-nums {{ (float) $balance < 0 ? 'text-red-700' : 'text-brand-700' }}">Q {{ number_format((float) $balance, 2) }}</p>
        </section>
    </div>

    <section class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-stone-200">
        <div class="border-b border-stone-200 px-5 py-4">
            <h2 class="text-lg font-bold">Movimientos del período</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200 text-sm">
                <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">
                    <tr>
                        <th class="px-5 py-3">Fecha</th>
                        <th class="px-5 py-3">Tipo</th>
                        <th class="px-5 py-3">Categoría</th>
                        <th class="px-5 py-3">Descripción</th>
                        <th class="px-5 py-3">Origen</th>
                        <th class="px-5 py-3 text-right">Importe</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($movements as $movement)
                        <tr>
                            <td class="px-5 py-4">{{ $movement->date }}</td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset ring-current/10 {{ $movement->movement_type === 'income' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">{{ $movement->type_label }}</span>
                            </td>
                            <td class="px-5 py-4">{{ $movement->category_label }}</td>
                            <td class="px-5 py-4">{{ $movement->description ?? 'Sin descripción' }}</td>
                            <td class="px-5 py-4">
                                @if ($movement->movement_type === 'income' && $movement->source_id)
                                    <a href="{{ route('pagos.show', $movement->source_id) }}" class="font-semibold text-brand-700 hover:text-brand-500">{{ $movement->origin_label }} {{ $movement->reference }}</a>
                                @elseif ($movement->movement_type === 'expense' && $movement->source_id)
                                    @can('view-purchases')
                                        <a href="{{ route('compras.show', $movement->source_id) }}" class="font-semibold text-brand-700 hover:text-brand-500">{{ $movement->origin_label }} {{ $movement->reference }}</a>
                                    @else
                                        {{ $movement->origin_label }} {{ $movement->reference }}
                                    @endcan
                                @else
                                    {{ $movement->origin_label }}
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right font-semibold tabular-nums {{ $movement->movement_type === 'income' ? 'text-emerald-700' : 'text-red-700' }}">
                                {{ $movement->movement_type === 'income' ? '+' : '-' }} Q {{ number_format((float) $movement->amount, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-8 text-center text-stone-500">No hay movimientos en este período.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{ $movements->links() }}
@endsection
