<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <title>@yield('title', 'Administración') | {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen overflow-x-hidden bg-cream text-stone-900 antialiased">
    <div data-sidebar-overlay class="pointer-events-none fixed inset-0 z-40 bg-stone-950/55 opacity-0 transition-opacity duration-200 lg:hidden" aria-hidden="true"></div>

    <aside id="admin-sidebar" data-admin-sidebar class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col bg-espresso text-stone-100 shadow-2xl transition-transform duration-200 ease-out lg:translate-x-0 lg:shadow-none" aria-label="Navegación principal">
        <div class="flex h-20 shrink-0 items-center justify-between border-b border-white/10 px-5">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 rounded-lg focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand-400">
                <span class="grid size-10 place-items-center rounded-xl bg-brand-700 text-lg font-black text-white shadow-sm ring-1 ring-white/10">S</span>
                <span>
                    <span class="block text-lg font-bold tracking-wide text-white">{{ config('app.name') }}</span>
                    <span class="block text-[0.68rem] font-medium uppercase tracking-[0.18em] text-stone-400">Gestión clínica dental</span>
                </span>
            </a>
            <button type="button" data-sidebar-close class="grid size-10 place-items-center rounded-lg text-stone-300 hover:bg-white/10 hover:text-white focus-visible:outline-2 focus-visible:outline-brand-400 lg:hidden" aria-label="Cerrar navegación">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" d="M6 6l12 12M18 6 6 18" />
                </svg>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto px-4 py-5 text-sm" aria-label="Secciones administrativas">
            <div class="flex flex-col gap-1">
                <x-admin.nav-link :active="request()->routeIs('dashboard')" href="{{ route('dashboard') }}">Dashboard</x-admin.nav-link>
            </div>

            @can('viewAny', \App\Models\Patient::class)
                <div class="mt-6">
                    <p class="px-3 text-[0.68rem] font-bold uppercase tracking-[0.18em] text-stone-500">Atención</p>
                    <div class="mt-2 flex flex-col gap-1">
                        <x-admin.nav-link :active="request()->routeIs('pacientes.*')" href="{{ route('pacientes.index') }}">Pacientes</x-admin.nav-link>
                        @can('viewAny', \App\Models\Appointment::class)
                            <x-admin.nav-link :active="request()->routeIs('citas.*')" href="{{ route('citas.index') }}">Citas</x-admin.nav-link>
                        @can('viewAny', \App\Models\Treatment::class)
                            <x-admin.nav-link :active="request()->routeIs('tratamientos.*')" href="{{ route('tratamientos.index') }}">Tratamientos</x-admin.nav-link>
                        @endcan
                        @endcan
                    </div>
                </div>
            @endcan

            @can('manage-users')
                <div class="mt-6">
                    <p class="px-3 text-[0.68rem] font-bold uppercase tracking-[0.18em] text-stone-500">Administración</p>
                    <div class="mt-2 flex flex-col gap-1">
                        <x-admin.nav-link :active="request()->routeIs('usuarios.*')" href="{{ route('usuarios.index') }}">Usuarios</x-admin.nav-link>
                    </div>
                </div>
            @endcan

            @if (app()->environment('testing'))
            @can('view-management-dashboard')
                <div class="mt-6">
                    <p class="px-3 text-[0.68rem] font-bold uppercase tracking-[0.18em] text-stone-500">Control de Gesti&#243;n</p>
                    <div class="mt-2 flex flex-col gap-1">
                        <x-admin.nav-link :active="request()->routeIs('gestion.reportes.*')" href="{{ route('gestion.reportes.index') }}">Reportes</x-admin.nav-link>
                    </div>
                </div>
            @endcan

            @can('manage-users')
                <div class="mt-6">
                    <p class="px-3 text-[0.68rem] font-bold uppercase tracking-[0.18em] text-stone-500">Administración</p>
                    <div class="mt-2 flex flex-col gap-1">
                        <x-admin.nav-link :active="request()->routeIs('usuarios.*')" href="{{ route('usuarios.index') }}">Usuarios</x-admin.nav-link>
                    </div>
                </div>
            @endcan

            @can('manage-employees')
                <div class="mt-6">
                    <p class="px-3 text-[0.68rem] font-bold uppercase tracking-[0.18em] text-stone-500">Recursos Humanos</p>
                    <div class="mt-2 flex flex-col gap-1">
                        <x-admin.nav-link :active="request()->routeIs('empleados.*')" href="{{ route('empleados.index') }}">Empleados</x-admin.nav-link>
                        <x-admin.nav-link :active="request()->routeIs('departamentos.*')" href="{{ route('departamentos.index') }}">Departamentos</x-admin.nav-link>
                        <x-admin.nav-link :active="request()->routeIs('puestos.*')" href="{{ route('puestos.index') }}">Puestos</x-admin.nav-link>
                    </div>
                </div>
            @endcan

            @can('manage-catalog')
                <div class="mt-6">
                    <p class="px-3 text-[0.68rem] font-bold uppercase tracking-[0.18em] text-stone-500">Catálogo</p>
                    <div class="mt-2 flex flex-col gap-1">
                        <x-admin.nav-link :active="request()->routeIs('categorias.*')" href="{{ route('categorias.index') }}">Categorías</x-admin.nav-link>
                        <x-admin.nav-link :active="request()->routeIs('productos.*')" href="{{ route('productos.index') }}">Productos</x-admin.nav-link>
                    </div>
                </div>

                <div class="mt-6">
                    <p class="px-3 text-[0.68rem] font-bold uppercase tracking-[0.18em] text-stone-500">Inventario</p>
                    <div class="mt-2 flex flex-col gap-1">
                        <x-admin.nav-link :active="request()->routeIs('inventario.existencias')" href="{{ route('inventario.existencias') }}">Existencias</x-admin.nav-link>
                        <x-admin.nav-link :active="request()->routeIs('inventario.movimientos.*')" href="{{ route('inventario.movimientos.index') }}">Movimientos</x-admin.nav-link>
                    </div>
                </div>
            @endcan

            @can('view-purchases')
                <div class="mt-6">
                    <p class="px-3 text-[0.68rem] font-bold uppercase tracking-[0.18em] text-stone-500">Compras</p>
                    <div class="mt-2 flex flex-col gap-1">
                        @can('manage-purchases')
                            <x-admin.nav-link :active="request()->routeIs('proveedores.*')" href="{{ route('proveedores.index') }}">Proveedores</x-admin.nav-link>
                        @endcan
                        <x-admin.nav-link :active="request()->routeIs('compras.*')" href="{{ route('compras.index') }}">Órdenes</x-admin.nav-link>
                    </div>
                </div>
            @endcan

            @can('manage-commercial')
                <div class="mt-6">
                    <p class="px-3 text-[0.68rem] font-bold uppercase tracking-[0.18em] text-stone-500">Comercial</p>
                    <div class="mt-2 flex flex-col gap-1">
                        @foreach ([
                            ['route' => 'clientes.index', 'active' => 'clientes.*', 'label' => 'Clientes'],
                            ['route' => 'cotizaciones.index', 'active' => 'cotizaciones.*', 'label' => 'Cotizaciones'],
                            ['route' => 'pedidos.index', 'active' => 'pedidos.*', 'label' => 'Pedidos'],
                            ['route' => 'ventas.index', 'active' => 'ventas.*', 'label' => 'Ventas'],
                            ['route' => 'consultas.index', 'active' => 'consultas.*', 'label' => 'Consultas'],
                        ] as $item)
                            <x-admin.nav-link :active="request()->routeIs($item['active'])" href="{{ route($item['route']) }}">{{ $item['label'] }}</x-admin.nav-link>
                        @endforeach
                    </div>
                </div>
            @endcan

            @can('manage-logistics')
                <div class="mt-6">
                    <p class="px-3 text-[0.68rem] font-bold uppercase tracking-[0.18em] text-stone-500">Operaciones</p>
                    <div class="mt-2 flex flex-col gap-1">
                        <x-admin.nav-link :active="request()->routeIs('operaciones.logistica.*')" href="{{ route('operaciones.logistica.index') }}">Logística</x-admin.nav-link>
                    </div>
                </div>
            @endcan

            @can('manage-commercial')
                <div class="mt-6">
                    <p class="px-3 text-[0.68rem] font-bold uppercase tracking-[0.18em] text-stone-500">Marketing</p>
                    <div class="mt-2 flex flex-col gap-1">
                        <x-admin.nav-link :active="request()->routeIs('marketing.analytics.*')" href="{{ route('marketing.analytics.index') }}">Analítica</x-admin.nav-link>
                    </div>
                </div>
            @endcan

            @can('view-invoices')
                <div class="mt-6">
                    <p class="px-3 text-[0.68rem] font-bold uppercase tracking-[0.18em] text-stone-500">Facturación</p>
                    <div class="mt-2 flex flex-col gap-1">
                        <x-admin.nav-link :active="request()->routeIs('facturas.*')" href="{{ route('facturas.index') }}">Facturas</x-admin.nav-link>
                        @can('manage-finances')
                            <x-admin.nav-link :active="request()->routeIs('pagos.*')" href="{{ route('pagos.index') }}">Pagos</x-admin.nav-link>
                            <x-admin.nav-link :active="request()->routeIs('notas-credito.*')" href="{{ route('notas-credito.index') }}">Notas de crédito</x-admin.nav-link>
                        @endcan
                    </div>
                </div>
            @endcan

            @can('manage-finances')
                <div class="mt-6">
                    <p class="px-3 text-[0.68rem] font-bold uppercase tracking-[0.18em] text-stone-500">Finanzas</p>
                    <div class="mt-2 flex flex-col gap-1">
                        @foreach ([
                            ['route' => 'finanzas.index', 'active' => 'finanzas.index', 'label' => 'Resumen'],
                            ['route' => 'finanzas.ingresos.index', 'active' => 'finanzas.ingresos.*', 'label' => 'Ingresos'],
                            ['route' => 'finanzas.gastos.index', 'active' => 'finanzas.gastos.*', 'label' => 'Gastos'],
                        ] as $item)
                            <x-admin.nav-link :active="request()->routeIs($item['active'])" href="{{ route($item['route']) }}">{{ $item['label'] }}</x-admin.nav-link>
                        @endforeach
                    </div>
                </div>
            @endcan
            @endif
        </nav>

        <div class="shrink-0 border-t border-white/10 px-5 py-4 text-xs text-stone-500">
            Sistema de gestión dental
        </div>
    </aside>

    <div class="min-h-screen lg:pl-72">
        <header class="sticky top-0 z-30 border-b border-brand-100 bg-brand-50/95 backdrop-blur">
            <div class="flex h-20 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                <div class="flex min-w-0 items-center gap-3">
                    <button type="button" data-sidebar-open class="grid size-10 shrink-0 place-items-center rounded-lg border border-brand-200 bg-white text-stone-700 shadow-sm hover:bg-brand-50 focus-visible:outline-2 focus-visible:outline-brand-600 lg:hidden" aria-controls="admin-sidebar" aria-expanded="false" aria-label="Abrir navegación">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" />
                        </svg>
                    </button>
                    <div class="min-w-0">
                        <p class="text-[0.68rem] font-bold uppercase tracking-[0.16em] text-brand-700">Panel administrativo</p>
                        <p class="truncate text-base font-semibold text-stone-900 sm:text-lg">@yield('heading')</p>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-3">
                    <div class="hidden text-right sm:block">
                        <p class="max-w-48 truncate text-sm font-semibold text-stone-800">{{ auth()->user()->name }}</p>
                        <p class="max-w-48 truncate text-xs text-stone-500">{{ auth()->user()->email }}</p>
                    </div>
                    <span class="grid size-9 place-items-center rounded-full bg-brand-100 text-sm font-bold text-brand-800 ring-1 ring-brand-200" aria-hidden="true">
                        {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                    </span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="ui-button ui-button-secondary">
                            <span class="hidden sm:inline">Cerrar sesión</span>
                            <span class="sm:hidden">Salir</span>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <main data-admin-content class="mx-auto flex w-full max-w-[100rem] flex-col gap-6 px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
            <div class="flex flex-col gap-4 border-b border-brand-100 pb-5 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-stone-950 sm:text-3xl">@yield('heading')</h1>
                    @hasSection('subheading')
                        <p class="mt-1.5 max-w-3xl text-sm leading-6 text-stone-600">@yield('subheading')</p>
                    @endif
                </div>
                <div class="flex shrink-0 flex-wrap gap-2">
                    @yield('actions')
                </div>
            </div>

            @if (session('status'))
                <div class="ui-alert-success" role="status">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('warning'))
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950" role="status">
                    {{ session('warning') }}
                </div>
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
    </div>
    @stack('scripts')
</body>
</html>
