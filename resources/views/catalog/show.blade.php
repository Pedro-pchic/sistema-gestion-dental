@extends('layouts.store')

@section('title', $product->name)
@section('heading', $product->name)
@section('subheading', $product->category->name.' · '.$product->sku)

@section('content')
    <section class="grid overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-stone-200 md:grid-cols-[1fr_20rem]">
        <div class="p-6 sm:p-8">
            <x-product-image :product="$product" width="960" loading="eager" class="mb-6 h-64 w-full rounded-2xl sm:h-80" />
            <h2 class="text-lg font-semibold">Descripción</h2>
            <p class="mt-3 whitespace-pre-line text-stone-700">{{ $product->description ?? 'Sin descripción disponible.' }}</p>
        </div>

        <aside class="border-t border-stone-200 bg-stone-50 p-6 md:border-l md:border-t-0">
            <p class="text-2xl font-bold tabular-nums">Q {{ number_format((float) $product->price, 2) }}</p>
            <p class="mt-2 text-sm font-semibold {{ $product->availabilityLabel() === 'Agotado' ? 'text-red-700' : ($product->availabilityLabel() === 'Stock bajo' ? 'text-amber-700' : 'text-emerald-700') }}">
                {{ $product->availabilityLabel() }}
            </p>

            <form method="POST" action="{{ route('carrito.store', $product) }}" class="mt-5 flex flex-col gap-3">
                @csrf
                <div>
                    <label for="quantity" class="block text-sm font-medium">Cantidad</label>
                    <input id="quantity" name="quantity" type="number" min="1" value="{{ old('quantity', 1) }}" required @disabled(! $product->isAvailable())
                        class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 disabled:cursor-not-allowed disabled:bg-stone-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
                </div>
                <button type="submit" @disabled(! $product->isAvailable())
                    class="rounded-lg bg-brand-700 px-4 py-2 font-semibold text-white hover:bg-brand-600 disabled:cursor-not-allowed disabled:bg-stone-400">
                    {{ $product->isAvailable() ? 'Agregar al carrito' : 'Producto agotado' }}
                </button>
            </form>
        </aside>
    </section>

    <div>
        <a href="{{ route('catalogo.index') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-500">← Volver al catálogo</a>
    </div>
@endsection
