@extends('layouts.admin')

@section('title', 'Historial de citas')
@section('heading', 'Historial de citas')
@section('subheading', $patient->first_name.' '.$patient->last_name.' · ficha '.$patient->record_number)

@section('content')
    <section class="ui-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200 text-sm">
                <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">
                    <tr>
                        <th scope="col" class="px-5 py-3">Fecha y hora</th>
                        <th scope="col" class="px-5 py-3">Odontólogo</th>
                        <th scope="col" class="px-5 py-3">Tratamiento previsto</th>
                        <th scope="col" class="px-5 py-3">Estado</th>
                        <th scope="col" class="px-5 py-3 text-right">Detalle</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($appointments as $appointment)
                        <tr>
                            <td class="px-5 py-4 tabular-nums">{{ $appointment->starts_at->setTimezone($timezone)->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-4">{{ $appointment->dentist?->user?->name ?? 'Odontólogo no disponible' }}</td>
                            <td class="px-5 py-4">{{ $appointment->planned_treatment }}</td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $appointment->status->colorClasses() }}">
                                    {{ $appointment->status->label() }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('citas.show', $appointment) }}" class="font-semibold text-brand-700 hover:underline">Ver cita</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-stone-500">Este paciente todavía no tiene citas registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($appointments->hasPages())
            <div class="border-t border-stone-200 px-5 py-4">
                {{ $appointments->links() }}
            </div>
        @endif
        <div class="border-t border-stone-200 px-5 py-4">
            <a href="{{ route('pacientes.show', $patient) }}" class="ui-button ui-button-secondary">Volver a la ficha</a>
        </div>
    </section>
@endsection
