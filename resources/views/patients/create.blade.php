@extends('layouts.admin')

@section('title', 'Registrar paciente')
@section('heading', 'Registrar paciente')
@section('subheading', 'Crea una ficha independiente de la cuenta de usuario')

@section('content')
    <form method="POST" action="{{ route('pacientes.store') }}" class="ui-card p-5 sm:p-6">
        @csrf
        @include('patients._form')
        <div class="mt-6 flex flex-wrap gap-3 border-t border-stone-200 pt-5">
            <button type="submit" class="ui-button ui-button-primary">Registrar paciente</button>
            <a href="{{ route('pacientes.index') }}" class="ui-button ui-button-secondary">Cancelar</a>
        </div>
    </form>
@endsection
