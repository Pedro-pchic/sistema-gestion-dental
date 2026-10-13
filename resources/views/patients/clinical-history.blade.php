@extends('layouts.admin')

@section('title', 'Historial clínico')
@section('heading', 'Historial clínico')
@section('subheading', $patient->first_name.' '.$patient->last_name.' · Ficha '.$patient->record_number)

@section('actions')
    <a href="{{ route('pacientes.show', $patient) }}" class="ui-button ui-button-secondary">Volver a la ficha</a>
    <a href="{{ route('pacientes.planes.index', $patient) }}" class="ui-button ui-button-secondary">Planes de tratamiento</a>
    @can('createClinicalRecord', $patient)
        <a href="{{ route('pacientes.tratamientos.create', $patient) }}" class="ui-button ui-button-primary">Registrar procedimiento</a>
        <a href="{{ route('pacientes.planes.create', $patient) }}" class="ui-button ui-button-secondary">Crear plan</a>
    @endcan
@endsection

@section('content')
    <nav class="flex flex-wrap gap-2 text-sm" aria-label="Secciones del expediente">
        <a href="#cronologia" class="ui-button ui-button-secondary">Cronología</a>
        <a href="#planes" class="ui-button ui-button-secondary">Planes</a>
        <a href="#citas" class="ui-button ui-button-secondary">Citas</a>
        <a href="#procedimientos" class="ui-button ui-button-secondary">Procedimientos</a>
    </nav>

    <section class="ui-card p-5">
        <h2 class="font-semibold text-stone-900">Información del paciente</h2>
        <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
            <div><dt class="text-stone-500">Fecha de nacimiento</dt><dd class="mt-1 font-medium">{{ $patient->date_of_birth?->format('d/m/Y') ?? 'Sin fecha' }}</dd></div>
            <div><dt class="text-stone-500">Teléfono</dt><dd class="mt-1 font-medium">{{ $patient->phone ?? 'Sin teléfono' }}</dd></div>
            <div><dt class="text-stone-500">Correo</dt><dd class="mt-1 font-medium">{{ $patient->email ?? 'Sin correo' }}</dd></div>
            <div><dt class="text-stone-500">Odontólogos asignados</dt><dd class="mt-1 font-medium">{{ $dentists->pluck('user.name')->filter()->join(', ') ?: 'Sin asignación' }}</dd></div>
            <div><dt class="text-stone-500">Estado</dt><dd class="mt-1 font-medium">{{ $patient->is_active ? 'Activo' : 'Inactivo' }}</dd></div>
            <div><dt class="text-stone-500">Registrado</dt><dd class="mt-1 font-medium">{{ $patient->created_at->format('d/m/Y') }}</dd></div>
        </dl>
    </section>

    <form method="GET" action="{{ route('pacientes.historial-clinico', $patient) }}" class="ui-card grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-3 lg:items-end">
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
        <div>
            <label for="record_type" class="block text-sm font-medium">Tipo en cronología</label>
            <select id="record_type" name="record_type" class="mt-2 w-full rounded-lg border border-stone-300 bg-white px-3 py-2">
                <option value="">Todos los registros</option>
                <option value="appointment" @selected(($filters['record_type'] ?? '') === 'appointment')>Citas</option>
                <option value="procedure" @selected(($filters['record_type'] ?? '') === 'procedure')>Procedimientos</option>
                <option value="update" @selected(($filters['record_type'] ?? '') === 'update')>Actualizaciones clínicas</option>
            </select>
        </div>
        <div class="flex gap-2"><button type="submit" class="ui-button ui-button-primary">Filtrar</button><a href="{{ route('pacientes.historial-clinico', $patient) }}" class="ui-button ui-button-secondary">Limpiar</a></div>
        <p class="text-xs text-stone-500 sm:col-span-2 lg:col-span-3">Fecha y odontólogo filtran citas y procedimientos. Tratamiento filtra procedimientos; tipo filtra solo la cronología. Los planes conservan su historial completo.</p>
    </form>

    <section id="cronologia" class="ui-card" aria-labelledby="timeline-heading">
        <div class="border-b border-stone-200 px-5 py-4">
            <h2 id="timeline-heading" class="font-semibold">Cronología odontológica</h2>
            <p class="mt-1 text-sm text-stone-500">Citas, procedimientos y actualizaciones clínicas, de lo más reciente a lo más antiguo.</p>
        </div>
        <ol class="divide-y divide-stone-200 px-5">
            @forelse ($activity as $entry)
                @php
                    $timelineAppointment = $entry->record_type === 'appointment' ? $activityAppointments->get($entry->record_id) : null;
                    $timelineProcedure = $entry->record_type === 'procedure' ? $activityProcedures->get($entry->record_id) : null;
                    $timelineUpdate = $entry->record_type === 'update' ? $activityUpdates->get($entry->record_id) : null;
                @endphp
                <li class="grid gap-2 py-4 sm:grid-cols-[10rem_1fr]">
                    <time datetime="{{ \Carbon\CarbonImmutable::parse($entry->occurred_at, 'UTC')->toIso8601String() }}" class="text-sm tabular-nums text-stone-500">{{ \Carbon\CarbonImmutable::parse($entry->occurred_at, 'UTC')->setTimezone($timezone)->format('d/m/Y H:i') }}</time>
                    <div class="min-w-0 text-sm text-stone-700">
                        @if ($timelineAppointment)
                            <span class="ui-badge {{ $timelineAppointment->status->colorClasses() }}">Cita · {{ $timelineAppointment->status->label() }}</span>
                            <p class="mt-2 font-semibold text-stone-900">{{ $timelineAppointment->planned_treatment }}</p>
                            <p>Odontólogo: {{ $timelineAppointment->dentist?->user?->name ?? 'No disponible' }}</p>
                            <a href="{{ route('citas.show', $timelineAppointment) }}" class="font-semibold text-brand-800 hover:underline">Ver cita</a>
                        @elseif ($timelineProcedure)
                            <span class="ui-badge ui-badge-success">Procedimiento</span>
                            <p class="mt-2 font-semibold text-stone-900">{{ $timelineProcedure->treatment?->name ?? 'Tratamiento no disponible' }}</p>
                            <p>Odontólogo: {{ $timelineProcedure->dentist?->user?->name ?? 'No disponible' }}</p>
                            <p class="mt-1 whitespace-pre-line">{{ $timelineProcedure->procedure_description }}</p>
                            @if ($timelineProcedure->clinical_notes)<p class="mt-1 whitespace-pre-line">Observaciones: {{ $timelineProcedure->clinical_notes }}</p>@endif
                            @if ($timelineProcedure->appointment)<p class="mt-1">Cita relacionada: {{ $timelineProcedure->appointment->starts_at->setTimezone($timezone)->format('d/m/Y H:i') }}</p>@endif
                        @elseif ($timelineUpdate)
                            <span class="ui-badge ui-badge-neutral">Actualización clínica</span>
                            <p class="mt-2 font-semibold text-stone-900">{{ $timelineUpdate->patientTreatment?->treatment?->name ?? 'Tratamiento no disponible' }}</p>
                            <p>Registrada por {{ $timelineUpdate->user?->name ?? 'Usuario no disponible' }}.</p>
                            <p class="mt-1 text-stone-600">Se actualizaron los datos del procedimiento.</p>
                        @endif
                    </div>
                </li>
            @empty
                <li class="py-8 text-center text-sm text-stone-500">No hay movimientos clínicos para los filtros seleccionados.</li>
            @endforelse
        </ol>
        @if ($activity->hasPages())<div class="border-t border-stone-200 px-5 py-3">{{ $activity->links() }}</div>@endif
    </section>

    <section id="planes" class="ui-card" aria-labelledby="plans-heading">
        <div class="border-b border-stone-200 px-5 py-4"><h2 id="plans-heading" class="font-semibold">Planes de tratamiento</h2></div>
        <div class="grid gap-3 p-5">
            @forelse ($plans as $plan)
                <article class="rounded-lg border border-stone-200 p-4">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <h3 class="font-semibold text-stone-900"><a href="{{ route('pacientes.planes.show', [$patient, $plan]) }}" class="text-brand-800 hover:underline">{{ $plan->title }}</a></h3>
                            <p class="mt-1 text-sm text-stone-500">{{ $plan->dentist?->user?->name ?? 'Odontólogo no disponible' }} · {{ $plan->completed_items_count }}/{{ $plan->items_count }} partidas completadas</p>
                        </div>
                        <span class="ui-badge {{ $plan->status->colorClasses() }}">{{ $plan->status->label() }}</span>
                    </div>
                    <ul class="mt-3 flex flex-wrap gap-2 text-xs">
                        @foreach ($plan->items as $item)
                            <li class="rounded-full bg-stone-100 px-2.5 py-1 text-stone-700">{{ $item->treatment?->name ?? 'Tratamiento no disponible' }} · {{ $item->status->label() }}</li>
                        @endforeach
                        @if ($plan->items_count > $plan->items->count())
                            <li class="rounded-full bg-brand-50 px-2.5 py-1 text-brand-800">+{{ $plan->items_count - $plan->items->count() }} más en el plan</li>
                        @endif
                    </ul>
                </article>
            @empty
                <p class="py-5 text-center text-sm text-stone-500">No hay planes de tratamiento registrados.</p>
            @endforelse
        </div>
        @if ($plans->hasPages())<div class="border-t border-stone-200 px-5 py-3">{{ $plans->links() }}</div>@endif
    </section>

    <section class="ui-card">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-stone-200 px-5 py-4">
            <div><h2 class="font-semibold">Tratamientos pendientes</h2><p class="mt-1 text-sm text-stone-500">Procedimientos planificados que aún no se han registrado como realizados.</p></div>
            <a href="{{ route('pacientes.planes.index', $patient) }}" class="text-sm font-semibold text-brand-800 hover:underline">Ver planes</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[42rem] divide-y divide-stone-200 text-sm">
                <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-600"><tr><th class="px-5 py-3">Plan</th><th class="px-5 py-3">Tratamiento</th><th class="px-5 py-3">Cantidad</th><th class="px-5 py-3">Estado</th></tr></thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($pendingPlanItems as $item)
                        <tr>
                            <td class="px-5 py-3"><a href="{{ route('pacientes.planes.show', [$patient, $item->plan]) }}" class="font-medium text-brand-800 hover:underline">{{ $item->plan->title }}</a></td>
                            <td class="px-5 py-3">{{ $item->treatment->name }}</td>
                            <td class="px-5 py-3">{{ $item->quantity }}</td>
                            <td class="px-5 py-3"><span class="ui-badge {{ $item->status->colorClasses() }}">{{ $item->status->label() }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-8 text-center text-stone-500">No hay procedimientos pendientes.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($pendingPlanItems->hasPages())<div class="border-t border-stone-200 px-5 py-3">{{ $pendingPlanItems->links() }}</div>@endif
    </section>

    <section id="citas" class="ui-card">
        <div class="border-b border-stone-200 px-5 py-4"><h2 class="font-semibold">Historial de citas</h2></div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[36rem] divide-y divide-stone-200 text-sm">
                <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-600"><tr><th class="px-5 py-3">Fecha</th><th class="px-5 py-3">Odontólogo</th><th class="px-5 py-3">Tratamiento previsto</th><th class="px-5 py-3">Estado</th><th class="px-5 py-3">Cita</th></tr></thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($appointments as $appointment)
                        <tr><td class="px-5 py-3">{{ $appointment->starts_at->setTimezone($timezone)->format('d/m/Y H:i') }}</td><td class="px-5 py-3">{{ $appointment->dentist?->user?->name ?? 'No disponible' }}</td><td class="px-5 py-3">{{ $appointment->planned_treatment }}</td><td class="px-5 py-3"><span class="ui-badge {{ $appointment->status->colorClasses() }}">{{ $appointment->status->label() }}</span></td><td class="px-5 py-3"><a href="{{ route('citas.show', $appointment) }}" class="font-semibold text-brand-800 hover:underline">Ver cita</a></td></tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-stone-500">No hay citas para los filtros seleccionados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($appointments->hasPages())<div class="border-t border-stone-200 px-5 py-3">{{ $appointments->links() }}</div>@endif
    </section>

    <section id="procedimientos" class="ui-card">
        <div class="border-b border-stone-200 px-5 py-4"><h2 class="font-semibold">Procedimientos realizados</h2><p class="mt-1 text-sm text-stone-500">Registro clínico de los tratamientos efectuados.</p></div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[56rem] divide-y divide-stone-200 text-sm">
                <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-600"><tr><th class="px-5 py-3">Fecha y tratamiento</th><th class="px-5 py-3">Odontólogo / cita</th><th class="px-5 py-3">Procedimiento y observaciones</th><th class="px-5 py-3">Acciones</th></tr></thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($procedures as $procedure)
                        <tr class="align-top">
                            <td class="px-5 py-4"><p class="font-medium">{{ $procedure->performed_at->setTimezone($timezone)->format('d/m/Y H:i') }}</p><p class="mt-1 text-stone-600">{{ $procedure->treatment->name }}</p>@if ($procedure->planItem)<p class="mt-1 text-xs text-stone-500">Plan: {{ $procedure->planItem->plan->title }}</p>@endif</td>
                            <td class="px-5 py-4"><p>{{ $procedure->dentist->user->name }}</p><p class="mt-1 text-xs text-stone-500">{{ $procedure->appointment ? 'Cita '.$procedure->appointment->starts_at->setTimezone($timezone)->format('d/m/Y H:i') : 'Sin cita' }}</p></td>
                            <td class="max-w-xl px-5 py-4"><p class="whitespace-pre-line">{{ $procedure->procedure_description }}</p>@if ($procedure->clinical_notes)<p class="mt-2 whitespace-pre-line text-stone-600"><span class="font-semibold">Observaciones:</span> {{ $procedure->clinical_notes }}</p>@endif</td>
                            <td class="px-5 py-4">@if ($ownDentistId !== null && $procedure->dentist_id === $ownDentistId)<a href="{{ route('pacientes.tratamientos.edit', [$patient, $procedure]) }}" class="ui-button ui-button-secondary ui-button-compact">Editar</a>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-8 text-center text-stone-500">No hay procedimientos realizados para los filtros seleccionados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($procedures->hasPages())<div class="border-t border-stone-200 px-5 py-3">{{ $procedures->links() }}</div>@endif
    </section>
@endsection
