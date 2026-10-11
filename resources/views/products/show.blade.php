@extends('layouts.admin')

@section('title', 'Detalle del producto')
@section('heading', $product->name)
@section('subheading', $product->sku)

@section('actions')
    <a href="{{ route('productos.edit', $product) }}" class="ui-button ui-button-primary">Editar producto</a>
@endsection

@section('content')
    <div class="ui-card p-6">
        <dl class="grid gap-6 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <dt class="text-sm font-medium text-stone-500">Fotografía</dt>
                <dd class="mt-2">
                    <x-product-image :product="$product" width="640" loading="eager" class="h-56 w-full max-w-md rounded-xl" />
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-stone-500">SKU</dt>
                <dd class="mt-1 font-mono font-semibold">{{ $product->sku }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-stone-500">Categoría</dt>
                <dd class="mt-1">{{ $product->category->name }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-stone-500">Precio</dt>
                <dd class="mt-1 tabular-nums">{{ number_format((float) $product->price, 2) }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-stone-500">Estado</dt>
                <dd class="mt-1 font-semibold">{{ $product->is_active ? 'Activo' : 'Inactivo' }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-sm font-medium text-stone-500">Descripción</dt>
                <dd class="mt-1 whitespace-pre-line">{{ $product->description ?? 'Sin descripción' }}</dd>
            </div>
        </dl>

        <div class="mt-6 flex flex-wrap gap-3 border-t border-stone-200 pt-5">
            <a href="{{ route('productos.index') }}" class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold hover:bg-stone-50">Volver</a>
            <form method="POST" action="{{ route('productos.status', $product) }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="rounded-lg px-4 py-2 text-sm font-semibold {{ $product->is_active ? 'bg-amber-100 text-amber-900 hover:bg-amber-200' : 'bg-emerald-100 text-emerald-900 hover:bg-emerald-200' }}">
                    {{ $product->is_active ? 'Desactivar' : 'Activar' }}
                </button>
            </form>
        </div>
    </div>
@endsection
