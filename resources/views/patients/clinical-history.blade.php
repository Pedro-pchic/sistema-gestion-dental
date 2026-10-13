@extends('layouts.admin')

@section('title', 'Historial clínico')
@section('heading', 'Historial clínico')
@section('subheading', $patient->first_name.' '.$patient->last_name.' · Ficha '.$patient->record_number)

@section('actions')
    <a href="{{ route('pacientes.planes.index', $patient) }}" class="ui-button ui-button-secondary">Planes de tratamiento</a>
    @can('createClinicalRecord', $patient)
        <a href="{{ route('pacientes.tratamientos.create', $patient) }}" class="ui-button ui-button-primary">Registrar procedimiento</a>
        <a href="{{ route('pacientes.planes.create', $patient) }}" class="ui-button ui-button-secondary">Crear plan</a>
    @endcan
@endsection

@section('content')
    <section class="ui-card p-5">
        <h2 class="font-semibold text-stone-900">Información del paciente</h2>
        <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
            <div><dt class="text-stone-500">Fecha de nacimiento</dt><dd class="mt-1 font-medium">{{ $patient->date_of_birth?->format('d/m/Y') ?? 'Sin fecha' }}</dd></div>
            <div><dt class="text-stone-500">Teléfono</dt><dd class="mt-1 font-medium">{{ $patient->phone ?? 'Sin teléfono' }}</dd></div>
            <div><dt class="text-stone-500">Correo</dt><dd class="mt-1 font-medium">{{ $patient->email ?? 'Sin correo' }}</dd></div>
            <div><dt class="text-stone-500">Odontólogos asignados</dt><dd class="mt-1 font-medium">{{ $dentists->pluck('user.name')->filter()->join(', ') ?: 'Sin asignación' }}</dd></div>
        </dl>
    </section>

    <form method="GET" action="{{ route('pacientes.historial-clinico', $patient) }}" class="grid gap-4 ui-card p-5 sm:grid-cols-2 lg:grid-cols-5 lg:items-end">
        <div><label for="date_from" class="block text-sm font-medium">Desde</label><input id="date_from" name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2"></div>
        <div><label for="date_to" class="block text-sm font-medium">Hasta</label><input id="date_to" name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2"></div>
        <div>
            <label for="dentist_id" class="block text-sm font-medium">Odontólogo</label>
            <select id="dentist_id" name="dentist_id" class="mt-2 w-full rounded-lg border border-stone-300 bg-white px-3 py-2">
                <option value="">Todos</option>
                @foreach ($dentists as $dentist)
                    <option value="{{ $dentist->id }}" @selected((string) ($filters['dentist_id'] ?? '') === (string) $dentist->id)>{{ $dentist->user->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="treatment_id" class="block text-sm font-medium">Tratamiento</label>
            <select id="treatment_id" name="treatment_id" class="mt-2 w-full rounded-lg border border-stone-300 bg-white px-3 py-2">
                <option value="">Todos</option>
                @foreach ($treatments as $treatment)
                    <option value="{{ $treatment->id }}" @selected((string) ($filters['treatment_id'] ?? '') === (string) $treatment->id)>{{ $treatment->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2"><button type="submit" class="ui-button ui-button-primary">Filtrar</button><a href="{{ route('pacientes.historial-clinico', $patient) }}" class="ui-button ui-button-secondary">Limpiar</a></div>
    </form>

    <section class="ui-card">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-stone-200 px-5 py-4">
            <div><h2 class="font-semibold">Tratamientos pendientes</h2><p class="mt-1 text-sm text-stone-500">Procedimientos planificados que aún no se han registrado como realizados.</p></div>
            <a href="{{ route('pacientes.planes.index', $patient) }}" class="text-sm font-semibold text-brand-800 hover:underline">Ver planes</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200 text-sm">
                <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-600"><tr><th class="px-5 py-3">Plan</th><th class="px-5 py-3">Tratamiento</th><th class="px-5 py-3">Cantidad</th><th class="px-5 py-3">Precio estimado</th><th class="px-5 py-3">Estado</th></tr></thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($pendingPlanItems as $item)
                        <tr>
                            <td class="px-5 py-3"><a href="{{ route('pacientes.planes.show', [$patient, $item->plan]) }}" class="font-medium text-brand-800 hover:underline">{{ $item->plan->title }}</a></td>
                            <td class="px-5 py-3">{{ $item->treatment->name }}</td>
                            <td class="px-5 py-3">{{ $item->quantity }}</td>
                            <td class="px-5 py-3 tabular-nums">Q {{ number_format((float) $item->estimated_unit_price * $item->quantity, 2) }}</td>
                            <td class="px-5 py-3"><span class="ui-badge {{ $item->status->colorClasses() }}">{{ $item->status->label() }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-stone-500">No hay procedimientos pendientes.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($pendingPlanItems->hasPages())<div class="border-t border-stone-200 px-5 py-3">{{ $pendingPlanItems->links() }}</div>@endif
    </section>

    <section class="ui-card">
        <div class="border-b border-stone-200 px-5 py-4"><h2 class="font-semibold">Historial de citas</h2></div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200 text-sm">
                <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-600"><tr><th class="px-5 py-3">Fecha</th><th class="px-5 py-3">Odontólogo</th><th class="px-5 py-3">Estado</th><th class="px-5 py-3">Cita</th></tr></thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($appointments as $appointment)
                        <tr><td class="px-5 py-3">{{ $appointment->starts_at->setTimezone($timezone)->format('d/m/Y H:i') }}</td><td class="px-5 py-3">{{ $appointment->dentist->user->name }}</td><td class="px-5 py-3">{{ $appointment->status->label() }}</td><td class="px-5 py-3"><a href="{{ route('citas.show', $appointment) }}" class="font-semibold text-brand-800 hover:underline">Ver cita</a></td></tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-8 text-center text-stone-500">No hay citas para los filtros seleccionados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($appointments->hasPages())<div class="border-t border-stone-200 px-5 py-3">{{ $appointments->links() }}</div>@endif
    </section>

    <section class="ui-card">
        <div class="border-b border-stone-200 px-5 py-4"><h2 class="font-semibold">Procedimientos realizados</h2><p class="mt-1 text-sm text-stone-500">Los precios reflejan el importe guardado al momento de cada procedimiento.</p></div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200 text-sm">
                <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-600"><tr><th class="px-5 py-3">Fecha y tratamiento</th><th class="px-5 py-3">Odontólogo / cita</th><th class="px-5 py-3">Procedimiento y observaciones</th><th class="px-5 py-3">Precio aplicado</th><th class="px-5 py-3">Acciones</th></tr></thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($procedures as $procedure)
                        <tr class="align-top">
                            <td class="px-5 py-4"><p class="font-medium">{{ $procedure->performed_at->setTimezone($timezone)->format('d/m/Y H:i') }}</p><p class="mt-1 text-stone-600">{{ $procedure->treatment->name }}</p>@if ($procedure->planItem)<p class="mt-1 text-xs text-stone-500">Plan: {{ $procedure->planItem->plan->title }}</p>@endif</td>
                            <td class="px-5 py-4"><p>{{ $procedure->dentist->user->name }}</p><p class="mt-1 text-xs text-stone-500">{{ $procedure->appointment ? 'Cita '.$procedure->appointment->starts_at->setTimezone($timezone)->format('d/m/Y H:i') : 'Sin cita' }}</p></td>
                            <td class="max-w-xl px-5 py-4"><p class="whitespace-pre-line">{{ $procedure->procedure_description }}</p>@if ($procedure->clinical_notes)<p class="mt-2 whitespace-pre-line text-stone-600"><span class="font-semibold">Observaciones:</span> {{ $procedure->clinical_notes }}</p>@endif</td>
                            <td class="px-5 py-4 font-medium tabular-nums">Q {{ number_format((float) $procedure->price_applied, 2) }}</td>
                            <td class="px-5 py-4">@can('update', $procedure)<a href="{{ route('pacientes.tratamientos.edit', [$patient, $procedure]) }}" class="ui-button ui-button-secondary ui-button-compact">Editar</a>@endcan</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-stone-500">No hay procedimientos realizados para los filtros seleccionados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($procedures->hasPages())<div class="border-t border-stone-200 px-5 py-3">{{ $procedures->links() }}</div>@endif
    </section>
@endsection
