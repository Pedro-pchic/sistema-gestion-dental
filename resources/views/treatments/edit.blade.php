@extends('layouts.admin')

@section('title', 'Editar tratamiento')
@section('heading', 'Editar tratamiento')
@section('subheading', $treatment->name)

@section('content')
    <form method="POST" action="{{ route('tratamientos.update', $treatment) }}" class="ui-card p-5 sm:p-6">
        @csrf
        @method('PUT')
        @include('treatments._form')
        <div class="mt-6 flex justify-end gap-3 border-t border-stone-200 pt-5">
            <a href="{{ route('tratamientos.show', $treatment) }}" class="ui-button ui-button-secondary">Cancelar</a>
            <button type="submit" class="ui-button ui-button-primary">Guardar cambios</button>
        </div>
    </form>
@endsection
