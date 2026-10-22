@extends('layouts.store')

@section('title', 'Inicio')
@section('meta_description', 'Sistema de gestión para una clínica dental.')

@section('content')
    <section class="relative isolate overflow-hidden rounded-[2rem] bg-coffee text-cream shadow-xl" aria-labelledby="hero-title">
        <div class="absolute -right-24 -top-24 size-80 rounded-full bg-blue-accent/25 blur-3xl" aria-hidden="true"></div>
        <div class="absolute -bottom-28 left-1/3 size-72 rounded-full bg-sand/10 blur-3xl" aria-hidden="true"></div>

        <div class="relative grid gap-8 p-6 sm:p-10 lg:min-h-[34rem] lg:grid-cols-[1.05fr_.95fr] lg:items-center lg:gap-12 lg:p-14">
            <div class="flex flex-col items-start gap-6">
                <p class="inline-flex items-center gap-2 rounded-full border border-sand/20 bg-white/5 px-3 py-1.5 text-xs font-bold uppercase tracking-[0.16em] text-sand">
                    <span class="size-2 rounded-full bg-blue-accent" aria-hidden="true"></span>
                    {{ config('app.name') }}
                </p>

                <div class="space-y-4">
                    <h1 id="hero-title" class="max-w-2xl text-4xl font-black leading-tight tracking-tight sm:text-5xl lg:text-6xl">
                        Encuentra el par para tu próximo paso.
                    </h1>
                    <p class="max-w-xl text-base leading-7 text-cream/75 sm:text-lg">
                        Explora estilos disponibles, consulta precios y compra desde nuestro catálogo en línea.
                    </p>
                </div>

                <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row">
                    <a href="{{ route('catalogo.index') }}" class="ui-button min-h-12 bg-blue-accent px-5 text-white hover:bg-white hover:text-coffee focus-visible:outline-white">
                        Explorar productos
                    </a>
                    <a href="{{ route('seguimiento.create') }}" class="ui-button min-h-12 border border-white/25 bg-white/5 px-5 text-cream hover:bg-white/15 focus-visible:outline-white">
                        Consultar pedido
                    </a>
                </div>
            </div>

            @if ($heroProduct)
                <figure class="relative rounded-[1.75rem] bg-white/5 p-3 shadow-lg ring-1 ring-white/10 sm:p-4">
                    <x-product-image :product="$heroProduct" width="960" loading="eager" class="aspect-[4/3] w-full rounded-2xl" />
                    <figcaption class="flex flex-col gap-4 px-2 pb-2 pt-5 sm:flex-row sm:items-end sm:justify-between sm:px-1">
                        <div class="min-w-0">
                            <p class="text-xs font-bold uppercase tracking-[0.16em] text-sand">Producto destacado</p>
                            <p class="mt-1 text-sm text-cream/70">{{ $heroProduct->category->name }}</p>
                            <h2 class="mt-1 truncate text-xl font-bold text-white sm:text-2xl">{{ $heroProduct->name }}</h2>
                        </div>
                        <div class="flex items-center justify-between gap-4 sm:flex-col sm:items-end">
                            <p class="text-xl font-bold tabular-nums text-white">Q {{ number_format((float) $heroProduct->price, 2) }}</p>
                            <a href="{{ route('catalogo.show', $heroProduct) }}" class="text-sm font-semibold text-sand underline decoration-sand/50 underline-offset-4 hover:text-white focus-visible:rounded focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white">
                                Ver producto
                            </a>
                        </div>
                    </figcaption>
                </figure>
            @else
                <div class="relative overflow-hidden rounded-[1.75rem] border border-white/10 bg-white/5 p-7 sm:p-10">
                    <div class="absolute -bottom-12 -right-8 size-48 rounded-full border border-sand/15" aria-hidden="true"></div>
                    <div class="absolute -bottom-5 -right-1 size-32 rounded-full border border-sand/15" aria-hidden="true"></div>
                    <div class="relative max-w-sm space-y-4">
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-sand">{{ config('app.name') }}</p>
                        <h2 class="text-2xl font-bold text-white sm:text-3xl">Descubre el calzado disponible.</h2>
                        <p class="leading-7 text-cream/75">Consulta estilos, categorías y existencias en el catálogo en línea.</p>
                        <a href="{{ route('catalogo.index') }}" class="inline-flex min-h-11 items-center font-semibold text-sand underline decoration-sand/50 underline-offset-4 hover:text-white focus-visible:rounded focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white">
                            Ir al catálogo <span class="ml-2" aria-hidden="true">→</span>
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </section>

    <section id="categorias" class="grid gap-6 py-4 sm:py-6" aria-labelledby="categories-title">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-accent">Encuentra tu estilo</p>
                <h2 id="categories-title" class="mt-2 text-2xl font-bold tracking-tight text-charcoal sm:text-3xl">Compra por categoría</h2>
            </div>
            <a href="{{ route('catalogo.index') }}" class="font-semibold text-coffee underline decoration-sand underline-offset-4 hover:text-blue-accent focus-visible:rounded focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-accent">
                Ver todas las categorías <span aria-hidden="true">→</span>
            </a>
        </div>

        @if ($categories->isEmpty())
            <div class="ui-empty-state border border-sand/70 text-coffee">Las categorías estarán disponibles próximamente.</div>
        @else
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($categories as $category)
                    <a href="{{ route('catalogo.index', ['category_id' => $category->id]) }}" class="group relative isolate flex min-h-44 flex-col justify-between overflow-hidden rounded-2xl border border-sand/80 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-accent sm:p-6">
                        <span class="absolute -right-7 -top-9 -z-10 size-32 rounded-full bg-sand/60 transition duration-300 group-hover:scale-110" aria-hidden="true"></span>
                        <span class="grid size-10 place-items-center rounded-xl bg-cream text-sm font-bold text-coffee ring-1 ring-sand/80" aria-hidden="true">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="mt-6 block">
                            <span class="block text-lg font-bold text-charcoal group-hover:text-blue-accent">{{ $category->name }}</span>
                            @if ($category->description)
                                <span class="mt-1 block line-clamp-2 text-sm leading-6 text-stone-600">{{ $category->description }}</span>
                            @endif
                            <span class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-coffee">Explorar categoría <span aria-hidden="true">→</span></span>
                        </span>
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    <section id="destacados" class="grid gap-6 py-4 sm:py-6" aria-labelledby="products-title">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-accent">Disponibles para compra</p>
                <h2 id="products-title" class="mt-2 text-2xl font-bold tracking-tight text-charcoal sm:text-3xl">Más calzado para descubrir</h2>
            </div>
            <a href="{{ route('catalogo.index') }}" class="font-semibold text-coffee underline decoration-sand underline-offset-4 hover:text-blue-accent focus-visible:rounded focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-accent">
                Explorar el catálogo <span aria-hidden="true">→</span>
            </a>
        </div>

        @if ($featuredProducts->isEmpty())
            @if ($heroProduct)
                <p class="rounded-2xl border border-sand/80 bg-white px-5 py-4 text-sm leading-6 text-stone-600">Consulta el catálogo para ver más opciones disponibles.</p>
            @else
                <div class="ui-empty-state border border-sand/70">
                    <p class="font-semibold text-charcoal">No hay productos disponibles para mostrar por ahora.</p>
                    <p class="mt-2 text-sm leading-6 text-stone-600">Vuelve a consultar el catálogo más adelante.</p>
                </div>
            @endif
        @else
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($featuredProducts as $product)
                    <article class="group overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-sand/80 transition duration-200 hover:-translate-y-1 hover:shadow-lg">
                        <a href="{{ route('catalogo.show', $product) }}" aria-label="Ver {{ $product->name }}" class="block overflow-hidden focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-blue-accent">
                            <x-product-image :product="$product" width="640" loading="lazy" class="aspect-[4/3] w-full transition duration-300 group-hover:scale-[1.02]" />
                        </a>
                        <div class="grid gap-4 p-5">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.16em] text-blue-accent">{{ $product->category->name }}</p>
                                <h3 class="mt-2 text-lg font-bold text-charcoal">{{ $product->name }}</h3>
                            </div>
                            <div class="flex items-end justify-between gap-3">
                                <p class="text-xl font-bold tabular-nums text-coffee">Q {{ number_format((float) $product->price, 2) }}</p>
                                <a href="{{ route('catalogo.show', $product) }}" class="text-sm font-semibold text-blue-accent underline decoration-sand underline-offset-4 hover:text-coffee focus-visible:rounded focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-accent">
                                    Ver detalle
                                </a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <section id="promociones" class="relative overflow-hidden rounded-3xl border border-sand bg-sand/45 px-6 py-8 sm:px-10 sm:py-10" aria-labelledby="promotions-title">
        <div class="grid gap-6 sm:grid-cols-[1fr_auto] sm:items-center">
            <div class="max-w-2xl">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-accent">Promociones</p>
                <h2 id="promotions-title" class="mt-2 text-2xl font-bold tracking-tight text-charcoal sm:text-3xl">Información de la clínica</h2>
                <p class="mt-3 leading-7 text-coffee">No hay promociones vigentes por ahora. Consulta el catálogo para ver los precios actuales.</p>
            </div>
            <a href="{{ route('catalogo.index') }}" class="ui-button w-full bg-coffee text-cream hover:bg-charcoal focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-accent sm:w-auto">
                Ver catálogo
            </a>
        </div>
    </section>

    <section class="grid gap-4 py-4 sm:grid-cols-2 sm:py-6 lg:grid-cols-3" aria-label="Beneficios de compra">
        @foreach ([
            ['title' => 'Disponibilidad clara', 'body' => 'Consulta productos con existencias disponibles antes de agregarlos al carrito.', 'number' => '01'],
            ['title' => 'Compra en línea', 'body' => 'Explora el catálogo, revisa cada detalle y continúa al carrito cuando estés listo.', 'number' => '02'],
            ['title' => 'Seguimiento de pedido', 'body' => 'Usa la opción de seguimiento para consultar el estado de tu pedido.', 'number' => '03'],
        ] as $benefit)
            <article class="rounded-2xl border border-sand/80 bg-white p-5 sm:p-6">
                <span class="grid size-10 place-items-center rounded-xl bg-cream text-xs font-bold text-blue-accent ring-1 ring-sand/80" aria-hidden="true">{{ $benefit['number'] }}</span>
                <h2 class="mt-5 text-lg font-bold text-charcoal">{{ $benefit['title'] }}</h2>
                <p class="mt-2 text-sm leading-6 text-stone-600">{{ $benefit['body'] }}</p>
            </article>
        @endforeach
    </section>

    <section class="flex flex-col gap-5 rounded-3xl bg-charcoal px-6 py-8 text-cream sm:flex-row sm:items-center sm:justify-between sm:px-10 sm:py-10">
        <div class="max-w-2xl">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-sand">Atención dental</p>
            <h2 class="mt-2 text-2xl font-bold sm:text-3xl">¿Tienes una consulta?</h2>
            <p class="mt-3 leading-7 text-cream/75">Escríbenos desde el formulario de contacto o revisa el estado de tu pedido.</p>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row">
            <a href="{{ route('contacto.create') }}" class="ui-button bg-blue-accent text-white hover:bg-white hover:text-coffee focus-visible:outline-white">Contactar</a>
            <a href="{{ route('seguimiento.create') }}" class="ui-button border border-white/25 bg-white/5 text-cream hover:bg-white/15 focus-visible:outline-white">Ver pedido</a>
        </div>
    </section>
@endsection
