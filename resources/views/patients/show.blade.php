@extends('layouts.admin')

@section('title', 'Ficha del paciente')
@section('heading', $patient->first_name.' '.$patient->last_name)
@section('subheading', 'Ficha '.$patient->record_number)

@section('actions')
    @can('update', $patient)
        <a href="{{ route('pacientes.edit', $patient) }}" class="ui-button ui-button-secondary">Editar ficha</a>
    @endcan
@endsection

@section('content')
    <section class="ui-card p-5 sm:p-6">
        <dl class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <dt class="text-sm font-medium text-stone-500">Código de ficha</dt>
                <dd class="mt-1 font-mono text-sm">{{ $patient->record_number }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-stone-500">Fecha de registro</dt>
                <dd class="mt-1">{{ $patient->created_at->format('d/m/Y') }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-stone-500">Fecha de nacimiento</dt>
                <dd class="mt-1">{{ $patient->date_of_birth?->format('d/m/Y') ?? 'Sin fecha registrada' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-stone-500">Estado</dt>
                <dd class="mt-1"><span class="ui-badge {{ $patient->is_active ? 'ui-badge-success' : 'ui-badge-neutral' }}">{{ $patient->is_active ? 'Activo' : 'Inactivo' }}</span></dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-stone-500">Teléfono</dt>
                <dd class="mt-1">{{ $patient->phone ?? 'Sin teléfono registrado' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-stone-500">Correo electrónico</dt>
                <dd class="mt-1">{{ $patient->email ?? 'Sin correo registrado' }}</dd>
            </div>
            <div class="sm:col-span-2 lg:col-span-3">
                <dt class="text-sm font-medium text-stone-500">Dirección</dt>
                <dd class="mt-1 whitespace-pre-line">{{ $patient->address ?? 'Sin dirección registrada' }}</dd>
            </div>
        </dl>

        @can('manage-patient-assignments')
            <div class="mt-6 border-t border-stone-200 pt-5">
                <h2 class="text-sm font-semibold text-stone-700">Odontólogos autorizados</h2>
                <ul class="mt-2 flex flex-wrap gap-2">
                    @forelse ($patient->dentists as $dentist)
                        <li class="rounded-full bg-brand-50 px-3 py-1 text-sm text-brand-800">{{ $dentist->user->name }}</li>
                    @empty
                        <li class="text-sm text-stone-500">Ningún odontólogo tiene acceso asignado.</li>
                    @endforelse
                </ul>
            </div>
        @endcan

        <div class="mt-6 flex flex-wrap gap-3 border-t border-stone-200 pt-5">
            <a href="{{ route('pacientes.index') }}" class="ui-button ui-button-secondary">Volver al listado</a>
            @can('view', $patient)
                <a href="{{ route('pacientes.citas.index', $patient) }}" class="ui-button ui-button-secondary">Historial de citas</a>
            @endcan

            @can('viewFinancialSummary', $patient)
                <a href="{{ route('pacientes.resumen-financiero', $patient) }}" class="ui-button ui-button-secondary">Resumen financiero</a>
            @endcan

            @can('view-dental-finances')
                <a href="{{ route('facturacion.facturas.create', ['patient_id' => $patient->id]) }}" class="ui-button ui-button-secondary">Crear factura</a>
            @endcan

            @can('viewClinicalHistory', $patient)
                <a href="{{ route('pacientes.historial-clinico', $patient) }}" class="ui-button ui-button-secondary">Historial clínico</a>
            @endcan

            @can('createClinicalRecord', $patient)
                <a href="{{ route('pacientes.tratamientos.create', $patient) }}" class="ui-button ui-button-primary">Registrar procedimiento</a>
                <a href="{{ route('pacientes.planes.create', $patient) }}" class="ui-button ui-button-secondary">Crear plan</a>
            @endcan

            @can('update', $patient)
                <form method="POST" action="{{ route('pacientes.status', $patient) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="ui-button {{ $patient->is_active ? 'ui-button-danger' : 'ui-button-secondary' }}">
                        {{ $patient->is_active ? 'Desactivar' : 'Activar' }}
                    </button>
                </form>
            @endcan

            @can('delete', $patient)
                <form method="POST" action="{{ route('pacientes.destroy', $patient) }}" data-confirm="¿Eliminar lógicamente esta ficha?">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="ui-button ui-button-danger">Eliminar ficha</button>
                </form>
            @endcan
        </div>
    </section>

    @if ($clinicalSummary !== null)
        <section class="ui-card p-5 sm:p-6" aria-labelledby="clinical-summary-heading">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 id="clinical-summary-heading" class="text-lg font-semibold text-stone-900">Resumen clínico</h2>
                <a href="{{ route('pacientes.historial-clinico', $patient) }}" class="ui-button ui-button-secondary">Abrir expediente integral</a>
            </div>
            <dl class="mt-5 grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
                <div><dt class="text-stone-500">Citas</dt><dd class="mt-1 text-lg font-semibold tabular-nums">{{ $patient->appointments_count }}</dd></div>
                <div><dt class="text-stone-500">Procedimientos realizados</dt><dd class="mt-1 text-lg font-semibold tabular-nums">{{ $patient->patient_treatments_count }}</dd></div>
                <div><dt class="text-stone-500">Planes de tratamiento</dt><dd class="mt-1 text-lg font-semibold tabular-nums">{{ $patient->treatment_plans_count }}</dd></div>
                <div><dt class="text-stone-500">Última atención completada</dt><dd class="mt-1 font-medium">{{ $clinicalSummary['lastAttention']?->ends_at?->setTimezone(config('appointments.timezone'))->format('d/m/Y H:i') ?? 'Sin atención completada' }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-stone-500">Próxima cita</dt><dd class="mt-1 font-medium">@if ($clinicalSummary['nextAppointment'])<a href="{{ route('citas.show', $clinicalSummary['nextAppointment']) }}" class="text-brand-800 hover:underline">{{ $clinicalSummary['nextAppointment']->starts_at->setTimezone(config('appointments.timezone'))->format('d/m/Y H:i') }} · {{ $clinicalSummary['nextAppointment']->planned_treatment }}</a>@else Sin cita próxima @endif</dd></div>
            </dl>
        </section>
    @endif
@endsection
