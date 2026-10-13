@extends('layouts.admin')

@section('title', 'Editar usuario')
@section('heading', 'Editar usuario')
@section('subheading', $user->name)

@section('content')
    <form method="POST" action="{{ route('usuarios.update', $user) }}" class="ui-card p-5 sm:p-6">
        @csrf
        @method('PUT')
        @include('users._form')

        <div class="mt-6 flex justify-end gap-3 border-t border-stone-200 pt-5">
            <a href="{{ route('usuarios.index') }}" class="ui-button ui-button-secondary">Cancelar</a>
            <button type="submit" class="ui-button ui-button-primary">Actualizar usuario</button>
        </div>
    </form>
@endsection
