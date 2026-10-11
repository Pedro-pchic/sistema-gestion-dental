<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <meta name="description" content="@yield('meta_description', 'Sistema de gestión dental y atención clínica.')">
    <link rel="canonical" href="{{ url()->current() }}">
    <title>@yield('title', 'Inicio') | {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen overflow-x-hidden bg-cream text-charcoal antialiased">
    <div class="flex min-h-screen flex-col">
        <header class="sticky top-0 z-30 border-b border-sand/80 bg-cream/95 shadow-sm backdrop-blur">
            <div class="mx-auto flex min-h-20 w-full max-w-7xl items-center justify-between gap-3 px-4 py-3 sm:px-6 lg:px-8">
                    <a href="{{ route('inicio') }}" class="flex min-w-0 items-center gap-3 rounded-lg focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-accent">
                    <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-blue-accent text-xl font-black tracking-tight text-white shadow-sm">S</span>
                    <span class="min-w-0">
                        <span class="block truncate text-lg font-black tracking-[0.08em] text-charcoal">{{ config('app.name') }}</span>
                        <span class="hidden text-[0.65rem] font-bold uppercase tracking-[0.18em] text-blue-accent sm:block">Gestión clínica dental</span>
                    </span>
                </a>

                <nav class="hidden items-center gap-1 text-sm font-semibold xl:flex" aria-label="Navegación principal">
                    @foreach ([
                        ['route' => 'inicio', 'active' => 'inicio', 'label' => 'Inicio'],
                        ['route' => 'catalogo.index', 'active' => 'catalogo.*', 'label' => 'Productos'],
                        ['route' => 'nosotros', 'active' => 'nosotros', 'label' => 'Nosotros'],
                        ['route' => 'contacto.create', 'active' => 'contacto.*', 'label' => 'Contacto'],
                        ['route' => 'seguimiento.create', 'active' => 'seguimiento.*', 'label' => 'Mi pedido'],
                    ] as $item)
                        <a href="{{ route($item['route']) }}" @class([
                            'rounded-lg px-3 py-2 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-accent',
                            'bg-sand text-coffee' => request()->routeIs($item['active']),
                            'text-coffee hover:bg-sand/60' => ! request()->routeIs($item['active']),
                        ])>{{ $item['label'] }}</a>
                    @endforeach
                    <a href="{{ route('inicio') }}#categorias" class="rounded-lg px-3 py-2 text-coffee hover:bg-sand/60 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-accent">Categorías</a>
                    <a href="{{ route('inicio') }}#promociones" class="rounded-lg px-3 py-2 text-coffee hover:bg-sand/60 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-accent">Promociones</a>
                    <a href="{{ route('carrito.index') }}" @class([
                        'rounded-lg px-3 py-2 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-accent',
                        'bg-sand text-coffee' => request()->routeIs('carrito.*', 'checkout.*'),
                        'text-coffee hover:bg-sand/60' => ! request()->routeIs('carrito.*', 'checkout.*'),
                    ])>Carrito</a>
                    @auth
                        <a href="{{ route('dashboard') }}" class="ui-button ui-button-secondary ui-button-compact">Administración</a>
                    @else
                        <a href="{{ route('login') }}" class="ui-button ui-button-secondary ui-button-compact">Iniciar sesión</a>
                    @endauth
                </nav>

                <details class="relative xl:hidden">
                    <summary class="grid size-11 cursor-pointer list-none place-items-center rounded-xl border border-sand bg-white text-coffee shadow-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-accent" aria-label="Abrir navegación">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" />
                        </svg>
                    </summary>
                    <nav class="absolute right-0 mt-3 flex w-64 flex-col gap-1 rounded-2xl border border-sand bg-cream p-3 text-sm font-semibold text-coffee shadow-xl" aria-label="Navegación móvil">
                        <a href="{{ route('inicio') }}" class="rounded-lg px-3 py-2 hover:bg-sand/60">Inicio</a>
                        <a href="{{ route('catalogo.index') }}" class="rounded-lg px-3 py-2 hover:bg-sand/60">Productos</a>
                        <a href="{{ route('inicio') }}#categorias" class="rounded-lg px-3 py-2 hover:bg-sand/60">Categorías</a>
                        <a href="{{ route('inicio') }}#promociones" class="rounded-lg px-3 py-2 hover:bg-sand/60">Promociones</a>
                        <a href="{{ route('nosotros') }}" class="rounded-lg px-3 py-2 hover:bg-sand/60">Nosotros</a>
                        <a href="{{ route('contacto.create') }}" class="rounded-lg px-3 py-2 hover:bg-sand/60">Contacto</a>
                        <a href="{{ route('seguimiento.create') }}" class="rounded-lg px-3 py-2 hover:bg-sand/60">Consultar pedido</a>
                        <a href="{{ route('carrito.index') }}" class="rounded-lg px-3 py-2 hover:bg-sand/60">Carrito</a>
                        @auth
                            <a href="{{ route('dashboard') }}" class="rounded-lg px-3 py-2 hover:bg-sand/60">Administración</a>
                        @else
                            <a href="{{ route('login') }}" class="rounded-lg px-3 py-2 hover:bg-sand/60">Iniciar sesión</a>
                        @endauth
                    </nav>
                </details>
            </div>
        </header>

        <main data-store-content class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 px-4 py-8 sm:px-6 sm:py-10 lg:px-8">
            @hasSection('heading')
                <div class="max-w-3xl">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-brand-700">{{ config('app.name') }}</p>
                    <h1 class="mt-2 text-3xl font-bold tracking-tight text-stone-950 sm:text-4xl">@yield('heading')</h1>
                    @hasSection('subheading')
                        <p class="mt-2 text-sm leading-6 text-stone-600 sm:text-base">@yield('subheading')</p>
                    @endif
                </div>
            @endif

            @if (session('status'))
                <div class="ui-alert-success" role="status">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="ui-alert-danger" role="alert">
                    <p class="font-semibold">Revisa los datos ingresados.</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>

        <footer class="mt-8 border-t border-coffee bg-charcoal text-cream/75">
            <div class="mx-auto grid w-full max-w-7xl gap-8 px-4 py-10 sm:grid-cols-2 sm:px-6 lg:grid-cols-[1fr_auto] lg:items-end lg:px-8">
                <div>
                    <a href="{{ route('inicio') }}" class="inline-flex items-center gap-3 rounded-lg text-cream focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-sand">
                        <span class="grid size-10 place-items-center rounded-xl bg-blue-accent text-lg font-black text-white" aria-hidden="true">S</span>
                        <span class="font-black tracking-[0.08em]">{{ config('app.name') }}</span>
                    </a>
                    <p class="mt-3 max-w-sm text-sm leading-6">Sistema de gestión dental.</p>
                </div>
                <nav class="flex flex-wrap gap-x-5 gap-y-3 text-sm font-semibold" aria-label="Navegación de pie de página">
                    <a href="{{ route('catalogo.index') }}" class="rounded hover:text-white focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-sand">Productos</a>
                    <a href="{{ route('inicio') }}#categorias" class="rounded hover:text-white focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-sand">Categorías</a>
                    <a href="{{ route('seguimiento.create') }}" class="rounded hover:text-white focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-sand">Mi pedido</a>
                    <a href="{{ route('carrito.index') }}" class="rounded hover:text-white focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-sand">Carrito</a>
                    <a href="{{ route('contacto.create') }}" class="rounded hover:text-white focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-sand">Contacto</a>
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded hover:text-white focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-sand">Administración</a>
                    @endauth
                </nav>
                <p class="border-t border-white/10 pt-4 text-xs text-cream/60 sm:col-span-2 lg:col-span-2">{{ config('app.name') }}</p>
            </div>
        </footer>
    </div>
</body>
</html>
