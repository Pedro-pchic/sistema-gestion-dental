@extends('layouts.admin')

@section('title', 'Editar paciente')
@section('heading', 'Editar paciente')
@section('subheading', $patient->first_name.' '.$patient->last_name)

@section('content')
    <form method="POST" action="{{ route('pacientes.update', $patient) }}" class="ui-card p-5 sm:p-6">
        @csrf
        @method('PUT')
        @include('patients._form')
        <div class="mt-6 flex flex-wrap gap-3 border-t border-stone-200 pt-5">
            <button type="submit" class="ui-button ui-button-primary">Guardar cambios</button>
            <a href="{{ route('pacientes.show', $patient) }}" class="ui-button ui-button-secondary">Cancelar</a>
        </div>
    </form>
@endsection
