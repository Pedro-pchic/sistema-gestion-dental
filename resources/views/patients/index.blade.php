@extends('layouts.admin')

@section('title', 'Pacientes')
@section('heading', 'Pacientes')
@section('subheading', 'Consulta y administra las fichas de pacientes')

@section('actions')
    @can('create', \App\Models\Patient::class)
        <a href="{{ route('pacientes.create') }}" class="ui-button ui-button-primary">Registrar paciente</a>
    @endcan
@endsection

@section('content')
    <form method="GET" action="{{ route('pacientes.index') }}" class="ui-card flex flex-col gap-3 p-4 sm:flex-row sm:items-end">
        <label for="search" class="grid flex-1 gap-2 text-sm font-semibold text-stone-700">
            Buscar por nombre, apellido o teléfono
            <input id="search" name="search" type="search" value="{{ $search }}" maxlength="100"
                class="min-h-11 rounded-lg border border-stone-300 bg-white px-3 text-stone-900 shadow-sm focus:border-brand-600 focus:outline-2 focus:outline-brand-600">
        </label>
        <div class="flex gap-2">
            <button type="submit" class="ui-button ui-button-primary">Buscar</button>
            @if ($search !== '')
                <a href="{{ route('pacientes.index') }}" class="ui-button ui-button-secondary">Limpiar</a>
            @endif
        </div>
    </form>

    <div class="mt-5 ui-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[48rem] divide-y divide-stone-200 text-sm">
                <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">
                    <tr>
                        <th class="px-5 py-3">Paciente</th>
                        <th class="px-5 py-3">Código de ficha</th>
                        <th class="px-5 py-3">Teléfono</th>
                        <th class="px-5 py-3">Estado</th>
                        <th class="px-5 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($patients as $patient)
                        <tr>
                            <td class="px-5 py-4">
                                <a href="{{ route('pacientes.show', $patient) }}" class="font-semibold text-brand-700 hover:text-brand-500">
                                    {{ $patient->first_name }} {{ $patient->last_name }}
                                </a>
                                <p class="mt-1 text-xs text-stone-500">{{ $patient->email ?? 'Sin correo registrado' }}</p>
                            </td>
                            <td class="px-5 py-4 font-mono text-xs">{{ $patient->record_number }}</td>
                            <td class="px-5 py-4">{{ $patient->phone ?? '—' }}</td>
                            <td class="px-5 py-4">
                                <span class="ui-badge {{ $patient->is_active ? 'ui-badge-success' : 'ui-badge-neutral' }}">
                                    {{ $patient->is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('pacientes.show', $patient) }}" class="font-semibold text-brand-700 hover:text-brand-500">Ver ficha</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-stone-500">
                                {{ $search !== '' ? 'No hay pacientes que coincidan con la búsqueda.' : 'Todavía no hay pacientes registrados.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($patients->hasPages())
            <div class="border-t border-stone-200 px-5 py-4">{{ $patients->links() }}</div>
        @endif
    </div>
@endsection
