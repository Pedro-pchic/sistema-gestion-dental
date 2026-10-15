@extends('layouts.admin')

@section('title', 'Roles y permisos')
@section('heading', 'Roles y permisos')
@section('subheading', 'Consulta los permisos activos de cada rol y administra su acceso.')

@section('content')
    <div class="grid gap-4 md:grid-cols-3">
        @forelse ($roles as $role)
            <article class="ui-card flex flex-col gap-4 p-5">
                <div>
                    <h2 class="text-lg font-bold">{{ $role->name }}</h2>
                    <p class="text-sm text-stone-600">{{ count($role->effectivePermissionNames()) }} permisos activos</p>
                </div>
                <a href="{{ route('roles.edit', $role) }}" class="ui-button ui-button-secondary self-start" aria-label="Ver permisos de {{ $role->name }}">Ver permisos</a>
            </article>
        @empty
            <p class="ui-card p-5 text-stone-600">No hay roles clínicos registrados.</p>
        @endforelse
    </div>
@endsection
