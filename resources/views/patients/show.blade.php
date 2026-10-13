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
                <dt class="text-sm font-medium text-stone-500">Fecha de nacimiento</dt>
                <dd class="mt-1">{{ $patient->date_of_birth?->format('d/m/Y') ?? 'Sin fecha registrada' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-stone-500">Estado</dt>
                <dd class="mt-1 font-semibold">{{ $patient->is_active ? 'Activo' : 'Inactivo' }}</dd>
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
                <a href="{{ route('pacientes.citas.index', $patient) }}" class="rounded-lg border border-brand-200 bg-brand-50 px-4 py-2 text-sm font-semibold text-brand-800 hover:bg-brand-100">Historial de citas</a>
            @endcan

            @can('viewFinancialSummary', $patient)
                <a href="{{ route('pacientes.resumen-financiero', $patient) }}" class="rounded-lg border border-brand-200 bg-brand-50 px-4 py-2 text-sm font-semibold text-brand-800 hover:bg-brand-100">Resumen financiero</a>
            @endcan

            @can('view-dental-finances')
                <a href="{{ route('facturacion.facturas.create', ['patient_id' => $patient->id]) }}" class="ui-button ui-button-secondary">Crear factura</a>
            @endcan

            @can('viewClinicalHistory', $patient)
                <a href="{{ route('pacientes.historial-clinico', $patient) }}" class="rounded-lg border border-brand-200 bg-brand-50 px-4 py-2 text-sm font-semibold text-brand-800 hover:bg-brand-100">Historial clínico</a>
            @endcan

            @can('createClinicalRecord', $patient)
                <a href="{{ route('pacientes.tratamientos.create', $patient) }}" class="ui-button ui-button-primary">Registrar procedimiento</a>
                <a href="{{ route('pacientes.planes.create', $patient) }}" class="ui-button ui-button-secondary">Crear plan</a>
            @endcan

            @can('update', $patient)
                <form method="POST" action="{{ route('pacientes.status', $patient) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="rounded-lg px-4 py-2 text-sm font-semibold {{ $patient->is_active ? 'bg-amber-100 text-amber-900 hover:bg-amber-200' : 'bg-emerald-100 text-emerald-900 hover:bg-emerald-200' }}">
                        {{ $patient->is_active ? 'Desactivar' : 'Activar' }}
                    </button>
                </form>
            @endcan

            @can('delete', $patient)
                <form method="POST" action="{{ route('pacientes.destroy', $patient) }}" onsubmit="return confirm('¿Eliminar lógicamente esta ficha?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-lg bg-red-50 px-4 py-2 text-sm font-semibold text-red-800 hover:bg-red-100">Eliminar ficha</button>
                </form>
            @endcan
        </div>
    </section>
@endsection
