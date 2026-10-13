@extends('layouts.admin')

@section('title', 'Crear usuario')
@section('heading', 'Crear usuario')
@section('subheading', 'Asigna credenciales y un rol de acceso')

@section('content')
    <form method="POST" action="{{ route('usuarios.store') }}" class="ui-card p-5 sm:p-6">
        @csrf
        @include('users._form')

        <div class="mt-6 flex justify-end gap-3 border-t border-stone-200 pt-5">
            <a href="{{ route('usuarios.index') }}" class="ui-button ui-button-secondary">Cancelar</a>
            <button type="submit" class="ui-button ui-button-primary">Guardar usuario</button>
        </div>
    </form>
@endsection
