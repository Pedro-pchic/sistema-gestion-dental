@extends('layouts.store')

@section('title', 'Catálogo')
@section('heading', 'Catálogo')
@section('subheading', 'Explora nuestros productos y consulta su disponibilidad')

@section('content')
    <form method="GET" action="{{ route('catalogo.index') }}" class="ui-card grid gap-4 p-5 sm:grid-cols-[1fr_14rem_auto] sm:items-end">
        <div>
            <label for="search" class="block text-sm font-medium">Buscar por nombre o SKU</label>
            <input id="search" name="search" type="search" value="{{ $search }}" maxlength="100" placeholder="Ej. Zapato o CAL-001"
                class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
        </div>
        <div>
            <label for="category_id" class="block text-sm font-medium">Categoría</label>
            <select id="category_id" name="category_id" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
                <option value="">Todas</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected($categoryId === $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="ui-button ui-button-primary">Filtrar</button>
    </form>

    @if ($products->isEmpty())
        <div class="ui-empty-state">
            <p class="font-semibold">No encontramos productos.</p>
            <p class="mt-1 text-sm text-stone-600">Prueba con otra búsqueda o categoría.</p>
        </div>
    @else
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($products as $product)
                <article class="ui-card group flex overflow-hidden transition-shadow hover:shadow-md sm:flex-col">
                    <x-product-image :product="$product" width="480" loading="lazy" class="h-28 w-28 shrink-0 sm:h-36 sm:w-full" />
                    <div class="flex min-w-0 flex-1 flex-col gap-4 p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-stone-500">{{ $product->category->name }}</p>
                            <h2 class="mt-1 text-lg font-bold">{{ $product->name }}</h2>
                            <p class="mt-1 text-xs text-stone-500">{{ $product->sku }}</p>
                        </div>
                        <span class="ui-badge {{ $product->availabilityLabel() === 'Agotado' ? 'ui-badge-danger' : ($product->availabilityLabel() === 'Stock bajo' ? 'ui-badge-warning' : 'ui-badge-success') }}">
                            {{ $product->availabilityLabel() }}
                        </span>
                    </div>
                    <p class="mt-auto text-xl font-bold tabular-nums">Q {{ number_format((float) $product->price, 2) }}</p>
                    <a href="{{ route('catalogo.show', $product) }}" class="ui-button ui-button-secondary w-full">Ver producto</a>
                    </div>
                </article>
            @endforeach
        </div>

        {{ $products->links() }}
    @endif
@endsection
