@extends('layouts.admin')

@section('title', 'Departamentos')
@section('heading', 'Departamentos')
@section('subheading', 'Organiza las áreas de Recursos Humanos')

@section('actions')
    <a href="{{ route('departamentos.create') }}" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600">Nuevo departamento</a>
@endsection

@section('content')
    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-stone-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200 text-sm">
                <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-600">
                    <tr>
                        <th class="px-4 py-3">Nombre</th>
                        <th class="px-4 py-3">Puestos</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($departments as $department)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $department->name }}</td>
                            <td class="px-4 py-3 text-stone-600">{{ $department->positions_count }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset ring-current/10 {{ $department->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-stone-200 text-stone-700' }}">
                                    {{ $department->is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('departamentos.show', $department) }}" class="rounded-md border border-stone-300 px-3 py-1.5 font-medium hover:bg-stone-50">Ver</a>
                                    <a href="{{ route('departamentos.edit', $department) }}" class="rounded-md border border-stone-300 px-3 py-1.5 font-medium hover:bg-stone-50">Editar</a>
                                    <form method="POST" action="{{ route('departamentos.status', $department) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="rounded-md px-3 py-1.5 font-medium {{ $department->is_active ? 'bg-amber-100 text-amber-900 hover:bg-amber-200' : 'bg-emerald-100 text-emerald-900 hover:bg-emerald-200' }}">
                                            {{ $department->is_active ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-stone-500">No hay departamentos registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($departments->hasPages())
            <div class="border-t border-stone-200 px-4 py-3">{{ $departments->links() }}</div>
        @endif
    </div>
@endsection
