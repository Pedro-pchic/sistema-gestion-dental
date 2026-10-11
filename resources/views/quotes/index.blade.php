@extends('layouts.admin')

@section('title', 'Cotizaciones')
@section('heading', 'Cotizaciones')
@section('subheading', 'Propuestas comerciales e historial de estados')

@section('actions')
    <a href="{{ route('cotizaciones.create') }}" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600">Nueva cotización</a>
@endsection

@section('content')
    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-stone-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200 text-sm">
                <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">
                    <tr>
                        <th class="px-5 py-3">Número</th>
                        <th class="px-5 py-3">Cliente</th>
                        <th class="px-5 py-3">Fecha</th>
                        <th class="px-5 py-3">Vigencia</th>
                        <th class="px-5 py-3 text-right">Total</th>
                        <th class="px-5 py-3">Estado</th>
                        <th class="px-5 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($quotes as $quote)
                        <tr>
                            <td class="px-5 py-4 font-semibold">
                                <a href="{{ route('cotizaciones.show', $quote) }}" class="text-brand-700 hover:text-brand-500">{{ $quote->number }}</a>
                            </td>
                            <td class="px-5 py-4">{{ $quote->customer->name }}</td>
                            <td class="px-5 py-4">{{ $quote->quote_date->format('Y-m-d') }}</td>
                            <td class="px-5 py-4">{{ $quote->valid_until?->format('Y-m-d') ?? '—' }}</td>
                            <td class="px-5 py-4 text-right tabular-nums">Q {{ number_format((float) $quote->total, 2) }}</td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset ring-current/10 {{ match ($quote->status) {
                                    \App\Enums\QuoteStatus::Draft => 'bg-stone-100 text-stone-700',
                                    \App\Enums\QuoteStatus::Sent => 'bg-brand-100 text-brand-800',
                                    \App\Enums\QuoteStatus::Accepted => 'bg-emerald-100 text-emerald-800',
                                    \App\Enums\QuoteStatus::Rejected => 'bg-red-100 text-red-800',
                                } }}">{{ $quote->status->label() }}</span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('cotizaciones.show', $quote) }}" class="font-semibold text-brand-700 hover:text-brand-500">Ver</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-10 text-center text-stone-500">Todavía no hay cotizaciones registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($quotes->hasPages())
            <div class="border-t border-stone-200 px-5 py-4">{{ $quotes->links() }}</div>
        @endif
    </div>
@endsection
