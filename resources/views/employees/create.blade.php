@extends('layouts.admin')

@section('title', 'Crear empleado')
@section('heading', 'Crear empleado')
@section('subheading', 'Registra los datos laborales y la asociación opcional con un usuario')

@section('content')
    <form method="POST" action="{{ route('empleados.store') }}" class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
        @csrf
        @include('employees._form')

        <div class="mt-6 flex justify-end gap-3 border-t border-stone-200 pt-5">
            <a href="{{ route('empleados.index') }}" class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold hover:bg-stone-50">Cancelar</a>
            <button type="submit" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600">Guardar empleado</button>
        </div>
    </form>
@endsection
