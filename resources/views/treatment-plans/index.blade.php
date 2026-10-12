<div>
    <!-- Happiness is not something readymade. It comes from your own actions. - Dalai Lama -->
</div>
@extends('layouts.admin')

@section('title', 'Planes de tratamiento')
@section('heading', 'Planes de tratamiento')
@section('subheading', $patient->first_name.' '.$patient->last_name.' · Ficha '.$patient->record_number)

@section('actions')
    @can('createClinicalRecord', $patient)
        <a href="{{ route('pacientes.planes.create', $patient) }}" class="ui-button ui-button-primary">Nuevo plan</a>
    @endcan
    <a href="{{ route('pacientes.historial-clinico', $patient) }}" class="ui-button ui-button-secondary">Volver al historial</a>
@endsection

@section('content')
    <div class="ui-table-wrap">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200 text-sm">
                <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-600"><tr><th class="px-4 py-3">Plan</th><th class="px-4 py-3">Odontólogo</th><th class="px-4 py-3">Procedimientos</th><th class="px-4 py-3">Estimado</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3 text-right">Acción</th></tr></thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($plans as $plan)
                        @php($estimatedTotal = $plan->items->sum(fn ($item) => (float) $item->estimated_unit_price * $item->quantity))
                        <tr>
                            <td class="px-4 py-3"><p class="font-semibold">{{ $plan->title }}</p><p class="mt-1 text-xs text-stone-500">{{ $plan->created_at->format('d/m/Y') }}</p></td>
                            <td class="px-4 py-3">{{ $plan->dentist->user->name }}</td>
                            <td class="px-4 py-3">{{ $plan->items->count() }}</td>
                            <td class="px-4 py-3 tabular-nums">Q {{ number_format($estimatedTotal, 2) }}</td>
                            <td class="px-4 py-3"><span class="ui-badge {{ $plan->status->colorClasses() }}">{{ $plan->status->label() }}</span></td>
                            <td class="px-4 py-3 text-right"><a href="{{ route('pacientes.planes.show', [$patient, $plan]) }}" class="ui-button ui-button-secondary ui-button-compact">Ver avance</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-stone-500">Este paciente todavía no tiene planes de tratamiento.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($plans->hasPages())<div class="border-t border-stone-200 px-4 py-3">{{ $plans->links() }}</div>@endif
    </div>
@endsection
