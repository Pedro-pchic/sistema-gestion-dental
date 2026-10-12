<div>
    <!-- An unexamined life is not worth living. - Socrates -->
</div>
@extends('layouts.admin')

@section('title', $treatment->name)
@section('heading', $treatment->name)
@section('subheading', 'Detalle del tratamiento del catálogo')

@section('actions')
    @can('update', $treatment)
        <a href="{{ route('tratamientos.edit', $treatment) }}" class="ui-button ui-button-secondary">Editar tratamiento</a>
    @endcan
@endsection

@section('content')
    <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
        <dl class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <div><dt class="text-sm font-medium text-stone-500">Precio vigente</dt><dd class="mt-1 text-lg font-semibold tabular-nums">Q {{ number_format((float) $treatment->price, 2) }}</dd></div>
            <div><dt class="text-sm font-medium text-stone-500">Duración estimada</dt><dd class="mt-1">{{ $treatment->estimated_duration_minutes }} minutos</dd></div>
            <div><dt class="text-sm font-medium text-stone-500">Estado</dt><dd class="mt-1"><span class="ui-badge {{ $treatment->is_active ? 'ui-badge-success' : 'ui-badge-neutral' }}">{{ $treatment->is_active ? 'Activo' : 'Inactivo' }}</span></dd></div>
            <div class="sm:col-span-2 lg:col-span-3"><dt class="text-sm font-medium text-stone-500">Descripción</dt><dd class="mt-1 whitespace-pre-line">{{ $treatment->description ?: 'Sin descripción registrada.' }}</dd></div>
            <div><dt class="text-sm font-medium text-stone-500">Creado</dt><dd class="mt-1">{{ $treatment->created_at->format('d/m/Y H:i') }}</dd></div>
            <div><dt class="text-sm font-medium text-stone-500">Última actualización</dt><dd class="mt-1">{{ $treatment->updated_at->format('d/m/Y H:i') }}</dd></div>
        </dl>
        <div class="mt-6 border-t border-stone-200 pt-5">
            <a href="{{ route('tratamientos.index') }}" class="ui-button ui-button-secondary">Volver al catálogo</a>
        </div>
    </section>
@endsection
