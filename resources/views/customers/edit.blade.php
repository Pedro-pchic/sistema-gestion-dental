@extends('layouts.admin')

@section('title', 'Editar cliente')
@section('heading', 'Editar cliente')
@section('subheading', $customer->name)

@section('content')
    <form method="POST" action="{{ route('clientes.update', $customer) }}" class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
        @csrf
        @method('PUT')
        @include('customers._form')
        <div class="mt-6 flex flex-wrap gap-3 border-t border-stone-200 pt-5">
            <button type="submit" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600">Guardar cambios</button>
            <a href="{{ route('clientes.show', $customer) }}" class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold hover:bg-stone-50">Cancelar</a>
        </div>
    </form>
@endsection
