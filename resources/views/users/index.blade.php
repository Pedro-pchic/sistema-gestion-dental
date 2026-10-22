@extends('layouts.admin')

@section('title', 'Usuarios')
@section('heading', 'Usuarios')
@section('subheading', 'Administración de accesos y roles')

@section('actions')
    <a href="{{ route('usuarios.create') }}" class="ui-button ui-button-primary">Nuevo usuario</a>
@endsection

@section('content')
    <div class="ui-table-wrap">
        <div class="ui-table-scroll">
            <table class="w-full min-w-[54rem] divide-y divide-stone-200 text-sm">
                <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-600">
                    <tr>
                        <th scope="col" class="px-4 py-3">Nombre</th>
                        <th scope="col" class="px-4 py-3">Correo</th>
                        <th scope="col" class="px-4 py-3">Rol</th>
                        <th scope="col" class="px-4 py-3">Acceso clínico</th>
                        <th scope="col" class="px-4 py-3">Estado</th>
                        <th scope="col" class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($users as $user)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $user->name }}</td>
                            <td class="px-4 py-3 text-stone-600">{{ $user->email }}</td>
                            <td class="px-4 py-3">{{ $user->role?->name ?? 'Sin rol' }}</td>
                            <td class="px-4 py-3">{{ $user->can_view_clinical_records ? 'Autorizado' : 'Sin autorización' }}</td>
                            <td class="px-4 py-3">
                                <span class="ui-badge {{ $user->is_active ? 'ui-badge-success' : 'ui-badge-neutral' }}">
                                    {{ $user->is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('usuarios.edit', $user) }}" class="ui-button ui-button-compact ui-button-secondary">Editar</a>
                                    <form method="POST" action="{{ route('usuarios.status', $user) }}" @if ($user->is_active) data-confirm="¿Desactivar el acceso de {{ $user->name }}?" @endif>
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="ui-button ui-button-compact {{ $user->is_active ? 'ui-button-danger' : 'ui-button-secondary' }}">
                                            {{ $user->is_active ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-stone-600">No hay usuarios registrados. Crea uno con «Nuevo usuario».</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($users->hasPages())
            <div class="border-t border-stone-200 px-4 py-3">{{ $users->links() }}</div>
        @endif
    </div>
@endsection
