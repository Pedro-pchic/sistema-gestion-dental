<div>
    <!-- It is quality rather than quantity that matters. - Lucius Annaeus Seneca -->
</div>
@extends('layouts.admin')

@section('title', 'Nuevo tratamiento')
@section('heading', 'Nuevo tratamiento')
@section('subheading', 'Agrega un tratamiento al catálogo')

@section('content')
    <form method="POST" action="{{ route('tratamientos.store') }}" class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
        @csrf
        @include('treatments._form')
        <div class="mt-6 flex justify-end gap-3 border-t border-stone-200 pt-5">
            <a href="{{ route('tratamientos.index') }}" class="ui-button ui-button-secondary">Cancelar</a>
            <button type="submit" class="ui-button ui-button-primary">Guardar tratamiento</button>
        </div>
    </form>
@endsection
