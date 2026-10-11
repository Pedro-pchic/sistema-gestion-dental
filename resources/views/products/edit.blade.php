@extends('layouts.admin')

@section('title', 'Editar producto')
@section('heading', 'Editar producto')
@section('subheading', $product->name)

@section('content')
    <form method="POST" action="{{ route('productos.update', $product) }}" enctype="multipart/form-data" class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
        @csrf
        @method('PUT')
        @include('products._form')

        <div class="mt-6 flex justify-end gap-3 border-t border-stone-200 pt-5">
            <a href="{{ route('productos.show', $product) }}" class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold hover:bg-stone-50">Cancelar</a>
            <button type="submit" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600">Actualizar producto</button>
        </div>
    </form>
@endsection
