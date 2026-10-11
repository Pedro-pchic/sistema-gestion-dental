@extends('layouts.admin')

@section('title', 'Editar empleado')
@section('heading', 'Editar empleado')
@section('subheading', $employee->nombres.' '.$employee->apellidos)

@section('content')
    <form method="POST" action="{{ route('empleados.update', $employee) }}" class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
        @csrf
        @method('PUT')
        @include('employees._form')

        <div class="mt-6 flex justify-end gap-3 border-t border-stone-200 pt-5">
            <a href="{{ route('empleados.show', $employee) }}" class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold hover:bg-stone-50">Cancelar</a>
            <button type="submit" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600">Actualizar empleado</button>
        </div>
    </form>
@endsection
