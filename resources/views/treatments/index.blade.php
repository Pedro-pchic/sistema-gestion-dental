<div>
    <!-- Act only according to that maxim whereby you can, at the same time, will that it should become a universal law. - Immanuel Kant -->
</div>
@extends('layouts.admin')

@section('title', 'Tratamientos')
@section('heading', 'Tratamientos')
@section('subheading', 'Catálogo odontológico y precios vigentes')

@section('actions')
    @can('create', \App\Models\Treatment::class)
        <a href="{{ route('tratamientos.create') }}" class="ui-button ui-button-primary">Nuevo tratamiento</a>
    @endcan
@endsection

@section('content')
    <form method="GET" action="{{ route('tratamientos.index') }}" class="grid gap-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-stone-200 sm:grid-cols-[1fr_12rem_auto] sm:items-end">
        <div>
            <label for="search" class="block text-sm font-medium">Buscar por nombre</label>
            <input id="search" name="search" type="search" value="{{ $filters['search'] ?? '' }}" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
        </div>
        <div>
            <label for="status" class="block text-sm font-medium">Estado</label>
            <select id="status" name="status" class="mt-2 w-full rounded-lg border border-stone-300 bg-white px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
                <option value="">Todos</option>
                <option value="active" @selected(($filters['status'] ?? '') === 'active')>Activo</option>
                <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactivo</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="ui-button ui-button-primary">Buscar</button>
            <a href="{{ route('tratamientos.index') }}" class="ui-button ui-button-secondary">Limpiar</a>
        </div>
    </form>

    <div class="ui-table-wrap">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200 text-sm">
                <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-600">
                    <tr>
                        <th class="px-4 py-3">Tratamiento</th>
                        <th class="px-4 py-3">Precio</th>
                        <th class="px-4 py-3">Duración</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($treatments as $treatment)
                        <tr>
                            <td class="px-4 py-3">
                                <p class="font-semibold">{{ $treatment->name }}</p>
                                <p class="mt-1 max-w-xl text-xs text-stone-500">{{ $treatment->description ?: 'Sin descripción' }}</p>
                            </td>
                            <td class="px-4 py-3 font-medium tabular-nums">Q {{ number_format((float) $treatment->price, 2) }}</td>
                            <td class="px-4 py-3">{{ $treatment->estimated_duration_minutes }} min</td>
                            <td class="px-4 py-3"><span class="ui-badge {{ $treatment->is_active ? 'ui-badge-success' : 'ui-badge-neutral' }}">{{ $treatment->is_active ? 'Activo' : 'Inactivo' }}</span></td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <a href="{{ route('tratamientos.show', $treatment) }}" class="ui-button ui-button-secondary ui-button-compact">Ver</a>
                                    @can('update', $treatment)
                                        <a href="{{ route('tratamientos.edit', $treatment) }}" class="ui-button ui-button-secondary ui-button-compact">Editar</a>
                                        <form method="POST" action="{{ route('tratamientos.status', $treatment) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="ui-button ui-button-compact {{ $treatment->is_active ? 'ui-button-secondary' : 'ui-button-primary' }}">{{ $treatment->is_active ? 'Desactivar' : 'Activar' }}</button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-stone-500">No hay tratamientos que coincidan con la búsqueda.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($treatments->hasPages())
            <div class="border-t border-stone-200 px-4 py-3">{{ $treatments->links() }}</div>
        @endif
    </div>
@endsection
