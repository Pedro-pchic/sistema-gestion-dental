@extends('layouts.admin')

@section('title', 'Productos')
@section('heading', 'Productos')
@section('subheading', 'Catálogo administrativo de calzado')

@section('actions')
    <a href="{{ route('productos.create') }}" class="ui-button ui-button-primary">Nuevo producto</a>
@endsection

@section('content')
    <div class="ui-table-wrap">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200 text-sm">
                <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-600">
                    <tr>
                        <th class="px-4 py-3">Fotografía</th>
                        <th class="px-4 py-3">SKU</th>
                        <th class="px-4 py-3">Nombre</th>
                        <th class="px-4 py-3">Categoría</th>
                        <th class="px-4 py-3">Precio</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($products as $product)
                        <tr>
                            <td class="px-4 py-3">
                                <x-product-image :product="$product" width="128" loading="lazy" class="h-12 w-12 rounded-md" />
                            </td>
                            <td class="px-4 py-3 font-mono text-xs font-semibold">{{ $product->sku }}</td>
                            <td class="px-4 py-3 font-medium">{{ $product->name }}</td>
                            <td class="px-4 py-3 text-stone-600">{{ $product->category->name }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ number_format((float) $product->price, 2) }}</td>
                            <td class="px-4 py-3">
                                <span class="ui-badge {{ $product->is_active ? 'ui-badge-success' : 'ui-badge-neutral' }}">
                                    {{ $product->is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <a href="{{ route('productos.show', $product) }}" class="ui-button ui-button-secondary ui-button-compact">Ver</a>
                                    <a href="{{ route('productos.edit', $product) }}" class="ui-button ui-button-secondary ui-button-compact">Editar</a>
                                    <form method="POST" action="{{ route('productos.status', $product) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="rounded-md px-3 py-1.5 font-medium {{ $product->is_active ? 'bg-amber-100 text-amber-900 hover:bg-amber-200' : 'bg-emerald-100 text-emerald-900 hover:bg-emerald-200' }}">
                                            {{ $product->is_active ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-stone-500">No hay productos registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($products->hasPages())
            <div class="border-t border-stone-200 px-4 py-3">{{ $products->links() }}</div>
        @endif
    </div>
@endsection
