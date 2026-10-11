@extends('layouts.admin')

@section('title', 'Ingresos')
@section('heading', 'Ingresos')
@section('subheading', 'Ingresos derivados de pagos e ingresos manuales justificados')

@section('actions')
    <a href="{{ route('finanzas.ingresos.create') }}" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600">Registrar ingreso manual</a>
@endsection

@section('content')
    <section class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-stone-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200 text-sm">
                <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">
                    <tr><th class="px-5 py-3">Fecha</th><th class="px-5 py-3">Categoría</th><th class="px-5 py-3">Descripción</th><th class="px-5 py-3">Origen</th><th class="px-5 py-3 text-right">Importe</th></tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($incomes as $income)
                        <tr>
                            <td class="px-5 py-4">{{ $income->date->format('Y-m-d') }}</td>
                            <td class="px-5 py-4">{{ $income->category->label() }}</td>
                            <td class="px-5 py-4">{{ $income->description ?? 'Sin descripción' }}</td>
                            <td class="px-5 py-4">
                                @if ($income->payment)
                                    <a href="{{ route('pagos.show', $income->payment) }}" class="font-semibold text-brand-700 hover:text-brand-500">Payment {{ $income->payment->number }}</a>
                                @else
                                    Manual
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right font-semibold text-emerald-700 tabular-nums">Q {{ number_format((float) $income->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-stone-500">No hay ingresos registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    {{ $incomes->links() }}
@endsection
