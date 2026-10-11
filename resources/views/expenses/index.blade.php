@extends('layouts.admin')

@section('title', 'Gastos')
@section('heading', 'Gastos')
@section('subheading', 'Gastos operativos y compras registradas explícitamente')

@section('actions')
    <a href="{{ route('finanzas.gastos.create') }}" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600">Registrar gasto</a>
@endsection

@section('content')
    <section class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-stone-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200 text-sm">
                <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">
                    <tr><th class="px-5 py-3">Fecha</th><th class="px-5 py-3">Categoría</th><th class="px-5 py-3">Descripción</th><th class="px-5 py-3">Origen</th><th class="px-5 py-3 text-right">Importe</th></tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($expenses as $expense)
                        <tr>
                            <td class="px-5 py-4">{{ $expense->date->format('Y-m-d') }}</td>
                            <td class="px-5 py-4">{{ $expense->category->label() }}</td>
                            <td class="px-5 py-4">{{ $expense->description }}</td>
                            <td class="px-5 py-4">
                                @if ($expense->purchase)
                                    @can('view-purchases')
                                        <a href="{{ route('compras.show', $expense->purchase) }}" class="font-semibold text-brand-700 hover:text-brand-500">Purchase {{ $expense->purchase->number }}</a>
                                    @else
                                        Purchase {{ $expense->purchase->number }}
                                    @endcan
                                @else
                                    Manual
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right font-semibold text-red-700 tabular-nums">Q {{ number_format((float) $expense->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-stone-500">No hay gastos registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    {{ $expenses->links() }}
@endsection
