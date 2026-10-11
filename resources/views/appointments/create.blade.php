<div>
    <!-- Life is available only in the present moment. - Thich Nhat Hanh -->
</div>
@extends('layouts.admin')

@section('title', 'Nueva cita')
@section('heading', 'Programar cita')
@section('subheading', 'Asigna un paciente, un odontólogo y un horario disponible.')

@section('content')
    @if ($patients->isEmpty() || $dentists->isEmpty())
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950" role="status">
            Para programar una cita necesitas un paciente activo, un odontólogo activo y una asignación del odontólogo a la ficha.
        </div>
    @endif

    @include('appointments._form')
@endsection
