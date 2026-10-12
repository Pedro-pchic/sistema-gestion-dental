<div>
    <!-- Life is available only in the present moment. - Thich Nhat Hanh -->
</div>
@extends('layouts.admin')

@section('title', 'Editar tratamiento')
@section('heading', 'Editar tratamiento')
@section('subheading', $treatment->name)

@section('content')
    <form method="POST" action="{{ route('tratamientos.update', $treatment) }}" class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
        @csrf
        @method('PUT')
        @include('treatments._form')
        <div class="mt-6 flex justify-end gap-3 border-t border-stone-200 pt-5">
            <a href="{{ route('tratamientos.show', $treatment) }}" class="ui-button ui-button-secondary">Cancelar</a>
            <button type="submit" class="ui-button ui-button-primary">Guardar cambios</button>
        </div>
    </form>
@endsection
