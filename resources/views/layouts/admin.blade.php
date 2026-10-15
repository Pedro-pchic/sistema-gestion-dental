<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <title>@yield('title', 'Administración') | {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen overflow-x-hidden bg-cream text-coffee antialiased">
    <a href="#main-content" class="sr-only fixed left-4 top-4 z-[60] rounded-xl bg-white px-4 py-3 font-semibold text-brand-900 shadow-lg focus:not-sr-only focus:outline-2 focus:outline-brand-600">Saltar al contenido</a>
    <div data-sidebar-overlay class="pointer-events-none fixed inset-0 z-40 bg-stone-950/55 opacity-0 transition-opacity duration-200 lg:hidden" aria-hidden="true"></div>

    <aside id="admin-sidebar" data-admin-sidebar class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col bg-espresso text-slate-100 shadow-2xl transition-[transform,width] duration-200 ease-out lg:translate-x-0 lg:shadow-none" aria-label="Navegación principal">
        <div class="flex h-20 shrink-0 items-center justify-between border-b border-white/10 px-5">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 rounded-lg focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand-400" aria-label="Ir al dashboard">
                <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-brand-300 text-brand-950 shadow-sm ring-1 ring-white/20" aria-hidden="true">
                    <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7">
                        <path d="M12 3c-1.7 0-2.1-.8-4-.8-2.5 0-4 2.1-4 4.5 0 3 1.8 5.7 3 8.8.7 1.9 1.4 3.6 2.5 3.6 1.6 0 1.7-4.8 3.5-4.8s1.9 4.8 3.5 4.8c1.1 0 1.8-1.7 2.5-3.6 1.2-3.1 3-5.8 3-8.8 0-2.4-1.5-4.5-4-4.5-1.9 0-2.3.8-4 .8Z" />
                    </svg>
                </span>
                <span data-sidebar-brand>
                    <span class="block text-base font-bold tracking-tight text-white">Clínica Dental</span>
                    <span class="block text-[0.67rem] font-semibold uppercase tracking-[0.16em] text-brand-200">Gestión odontológica</span>
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
                <x-admin.nav-link icon="grid" :active="request()->routeIs('dashboard')" href="{{ route('dashboard') }}">Dashboard</x-admin.nav-link>
            </div>

            @can('viewAny', \App\Models\Patient::class)
                <div class="mt-6">
                    <p class="px-3 text-[0.68rem] font-bold uppercase tracking-[0.18em] text-stone-500">Atención</p>
                    <div class="mt-2 flex flex-col gap-1">
                        <x-admin.nav-link icon="patients" :active="request()->routeIs('pacientes.*')" href="{{ route('pacientes.index') }}">Pacientes</x-admin.nav-link>
                        @can('viewAny', \App\Models\Appointment::class)
                            <x-admin.nav-link icon="calendar" :active="request()->routeIs('citas.*')" href="{{ route('citas.index') }}">Citas</x-admin.nav-link>
                            @can('appointment_requests.view')
                                <x-admin.nav-link icon="calendar" :active="request()->routeIs('solicitudes-cita.*')" href="{{ route('solicitudes-cita.index') }}">Solicitudes web</x-admin.nav-link>
                            @endcan
                            @can('view-appointment-reminders')
                                <x-admin.nav-link icon="calendar" :active="request()->routeIs('recordatorios.*')" href="{{ route('recordatorios.index') }}">Recordatorios</x-admin.nav-link>
                            @endcan
                        @can('viewAny', \App\Models\Treatment::class)
                            <x-admin.nav-link icon="treatment" :active="request()->routeIs('tratamientos.*')" href="{{ route('tratamientos.index') }}">Tratamientos</x-admin.nav-link>
                        @endcan
                        @endcan
                    </div>
                </div>
            @endcan

            @can('view-dental-finances')
                <div class="mt-6">
                    <p class="px-3 text-[0.68rem] font-bold uppercase tracking-[0.18em] text-stone-500">Facturación dental</p>
                    <div class="mt-2 flex flex-col gap-1">
                        <x-admin.nav-link icon="invoice" :active="request()->routeIs('facturacion.facturas.*')" href="{{ route('facturacion.facturas.index') }}">Facturas</x-admin.nav-link>
                        <x-admin.nav-link icon="payment" :active="request()->routeIs('facturacion.pagos.*')" href="{{ route('facturacion.pagos.index') }}">Pagos</x-admin.nav-link>
                        @can('view-dental-reports')
                            <x-admin.nav-link icon="report" :active="request()->routeIs('facturacion.reportes.*')" href="{{ route('facturacion.reportes.index') }}">Reportes</x-admin.nav-link>
                        @endcan
                    </div>
                </div>
            @endcan

            @if (auth()->user()->can('manage-users') || auth()->user()->can('manage-announcements') || auth()->user()->can('manage-public-site') || auth()->user()->can('manage-dentists-public'))
                <div class="mt-6">
                    <p class="px-3 text-[0.68rem] font-bold uppercase tracking-[0.18em] text-stone-500">Administración</p>
                    <div class="mt-2 flex flex-col gap-1">
                        @can('manage-users')
                            <x-admin.nav-link icon="users" :active="request()->routeIs('usuarios.*')" href="{{ route('usuarios.index') }}">Usuarios</x-admin.nav-link>
                        @endcan
                        @can('manage-announcements')
                            <x-admin.nav-link :active="request()->routeIs('anuncios.*')" href="{{ route('anuncios.index') }}">Anuncios</x-admin.nav-link>
                        @endcan
                        @can('manage-public-site')
                            <x-admin.nav-link :active="request()->routeIs('configuracion-publica.*')" href="{{ route('configuracion-publica.edit') }}">Sitio público</x-admin.nav-link>
                        @endcan
                        @can('manage-dentists-public')
                            <x-admin.nav-link :active="request()->routeIs('odontologos-publicos.*')" href="{{ route('odontologos-publicos.index') }}">Equipo público</x-admin.nav-link>
                        @endcan
                    </div>
                </div>
            @endif

            @can('roles.view')
                <div class="mt-6">
                    <p class="px-3 text-[0.68rem] font-bold uppercase tracking-[0.18em] text-stone-500">Seguridad</p>
                    <div class="mt-2 flex flex-col gap-1">
                        <x-admin.nav-link :active="request()->routeIs('roles.*')" href="{{ route('roles.index') }}">Roles y permisos</x-admin.nav-link>
                        @can('audit.view')
                            <x-admin.nav-link :active="request()->routeIs('audit.*')" href="{{ route('audit.index') }}">Auditoría del sistema</x-admin.nav-link>
                        @endcan
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

        <div data-sidebar-footer class="shrink-0 border-t border-white/10 px-5 py-4 text-xs text-brand-100">
            Sistema de gestión dental
        </div>
    </aside>

    <div data-admin-layout class="min-h-screen transition-[padding] duration-200 lg:pl-72">
        <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/95 shadow-[0_4px_20px_-20px_rgba(23,43,77,0.7)] backdrop-blur">
            <div class="flex min-h-20 items-center justify-between gap-3 px-4 py-3 sm:px-6 lg:px-8">
                <div class="flex min-w-0 items-center gap-3">
                    <button type="button" data-sidebar-open class="grid size-10 shrink-0 place-items-center rounded-xl border border-brand-200 bg-white text-brand-800 hover:bg-brand-50 focus-visible:outline-2 focus-visible:outline-brand-600 lg:hidden" aria-controls="admin-sidebar" aria-expanded="false" aria-label="Abrir navegación">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" />
                        </svg>
                    </button>
                    <button type="button" data-sidebar-toggle class="hidden size-10 shrink-0 place-items-center rounded-xl border border-brand-200 bg-white text-brand-800 hover:bg-brand-50 focus-visible:outline-2 focus-visible:outline-brand-600 lg:grid" aria-controls="admin-sidebar" aria-expanded="true" aria-label="Contraer navegación" title="Contraer navegación">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="2" aria-hidden="true">
                            <path d="M4 7h16M4 12h16M4 17h16" />
                        </svg>
                    </button>
                    <div class="min-w-0">
                        <p class="text-[0.68rem] font-bold uppercase tracking-[0.16em] text-brand-700">Espacio clínico</p>
                        <h1 class="truncate text-lg font-bold tracking-tight text-coffee sm:text-xl">@yield('heading')</h1>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-3">
                    <div class="hidden text-right sm:block">
                        <p class="max-w-48 truncate text-sm font-semibold text-coffee">{{ auth()->user()->name }}</p>
                        <p class="max-w-48 truncate text-xs text-brand-700">{{ auth()->user()->role?->name ?? 'Usuario' }}</p>
                    </div>
                    <span class="grid size-9 place-items-center rounded-full bg-brand-100 text-sm font-bold text-brand-800 ring-1 ring-brand-200" role="img" aria-label="{{ auth()->user()->name }}, {{ auth()->user()->role?->name ?? 'Usuario' }}">
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

        <main id="main-content" data-admin-content tabindex="-1" class="mx-auto flex w-full max-w-[100rem] min-w-0 flex-col gap-6 px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    @unless (request()->routeIs('dashboard'))
                        <nav class="mb-2 text-xs font-semibold text-brand-700" aria-label="Ruta de navegación">
                            <a href="{{ route('dashboard') }}" class="rounded focus-visible:outline-2 focus-visible:outline-brand-600">Inicio</a>
                            <span class="px-1 text-slate-400" aria-hidden="true">/</span>
                            <span aria-current="page">@yield('heading')</span>
                        </nav>
                    @endunless
                    @hasSection('subheading')
                        <p class="max-w-3xl text-sm leading-6 text-slate-600">@yield('subheading')</p>
                    @endif
                </div>
                @hasSection('actions')
                    <div class="flex flex-wrap gap-2">@yield('actions')</div>
                @endif
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
