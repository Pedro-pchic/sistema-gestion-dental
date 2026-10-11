@extends('layouts.admin')

@section('title', 'Agregar dirección')
@section('heading', 'Agregar dirección')
@section('subheading', $customer->name)

@section('content')
    <form method="POST" action="{{ route('clientes.direcciones.store', $customer) }}" class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
        @csrf
        @include('customers.addresses._form')
        <div class="mt-6 flex flex-wrap gap-3 border-t border-stone-200 pt-5">
            <button type="submit" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600">Guardar dirección</button>
            <a href="{{ route('clientes.show', $customer) }}" class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold hover:bg-stone-50">Cancelar</a>
        </div>
    </form>
@endsection
