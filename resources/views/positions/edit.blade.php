@extends('layouts.admin')

@section('title', 'Editar puesto')
@section('heading', 'Editar puesto')
@section('subheading', $position->name)

@section('content')
    <form method="POST" action="{{ route('puestos.update', $position) }}" class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
        @csrf
        @method('PUT')
        @include('positions._form')

        <div class="mt-6 flex justify-end gap-3 border-t border-stone-200 pt-5">
            <a href="{{ route('puestos.show', $position) }}" class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold hover:bg-stone-50">Cancelar</a>
            <button type="submit" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600">Actualizar puesto</button>
        </div>
    </form>
@endsection
