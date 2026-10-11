@extends('layouts.admin')

@section('title', 'Editar departamento')
@section('heading', 'Editar departamento')
@section('subheading', $department->name)

@section('content')
    <form method="POST" action="{{ route('departamentos.update', $department) }}" class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
        @csrf
        @method('PUT')
        @include('departments._form')

        <div class="mt-6 flex justify-end gap-3 border-t border-stone-200 pt-5">
            <a href="{{ route('departamentos.show', $department) }}" class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold hover:bg-stone-50">Cancelar</a>
            <button type="submit" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600">Actualizar departamento</button>
        </div>
    </form>
@endsection
