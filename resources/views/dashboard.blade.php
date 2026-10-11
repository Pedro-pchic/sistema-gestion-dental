@extends('layouts.admin')

@section('title', 'Dashboard')
@section('heading', 'Dashboard')
@section('subheading', $isManagementDashboard ? 'Indicadores operativos y financieros del período seleccionado' : ($isDentalDashboard ? 'Vista general del consultorio dental' : 'Vista general de usuarios y equipo de trabajo'))

@section('content')
    @if ($isManagementDashboard)
        <form method="GET" action="{{ route('dashboard') }}" class="ui-card grid gap-4 p-5 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
            <label class="grid gap-2 text-sm font-semibold text-stone-700">
                Fecha desde
                <input type="date" name="from" value="{{ $from }}" required class="min-h-11 rounded-lg border border-stone-300 bg-white px-3 text-stone-900 shadow-sm focus:border-brand-600 focus:outline-2 focus:outline-brand-600">
            </label>
            <label class="grid gap-2 text-sm font-semibold text-stone-700">
                Fecha hasta
                <input type="date" name="to" value="{{ $to }}" required class="min-h-11 rounded-lg border border-stone-300 bg-white px-3 text-stone-900 shadow-sm focus:border-brand-600 focus:outline-2 focus:outline-brand-600">
            </label>
            <button type="submit" class="ui-button ui-button-primary">Filtrar período</button>
        </form>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Resumen gerencial">
            @foreach ([
                ['label' => 'Ventas confirmadas', 'value' => $dashboardMetrics['sales']['count'], 'context' => 'Q '.number_format((float) $dashboardMetrics['sales']['total'], 2).' en el período'],
                ['label' => 'Ingresos', 'value' => 'Q '.number_format((float) $dashboardMetrics['finance']['income'], 2), 'context' => 'Registrados en Finanzas'],
                ['label' => 'Gastos', 'value' => 'Q '.number_format((float) $dashboardMetrics['finance']['expense'], 2), 'context' => 'Registrados en Finanzas'],
                ['label' => 'Balance', 'value' => 'Q '.number_format((float) $dashboardMetrics['finance']['balance'], 2), 'context' => 'Ingresos menos gastos'],
            ] as $metric)
                <article class="ui-card relative overflow-hidden p-5">
                    <span class="absolute inset-y-0 left-0 w-1 bg-brand-600" aria-hidden="true"></span>
                    <p class="text-sm font-semibold text-stone-600">{{ $metric['label'] }}</p>
                    <p class="mt-3 break-words text-2xl font-bold tracking-tight text-stone-950 tabular-nums">{{ $metric['value'] }}</p>
                    <p class="mt-2 text-xs text-stone-500">{{ $metric['context'] }}</p>
                </article>
            @endforeach
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Indicadores comerciales e inventario">
            @foreach ([
                ['label' => 'Ticket promedio', 'value' => 'Q '.number_format((float) $dashboardMetrics['sales']['average_ticket'], 2), 'context' => 'Ventas confirmadas'],
                ['label' => 'Clientes con actividad', 'value' => $dashboardMetrics['sales']['active_customers'], 'context' => 'Con ventas confirmadas'],
                ['label' => 'Productos bajo mínimo', 'value' => $dashboardMetrics['inventory']['low_stock'], 'context' => 'Con existencia positiva'],
                ['label' => 'Productos agotados', 'value' => $dashboardMetrics['inventory']['out_of_stock'], 'context' => 'Existencia en cero'],
            ] as $metric)
                <article class="ui-card p-5">
                    <p class="text-sm font-semibold text-stone-600">{{ $metric['label'] }}</p>
                    <p class="mt-3 text-3xl font-bold tracking-tight text-stone-950 tabular-nums">{{ $metric['value'] }}</p>
                    <p class="mt-2 text-xs text-stone-500">{{ $metric['context'] }}</p>
                </article>
            @endforeach
        </section>

        <section class="grid gap-6 xl:grid-cols-2" aria-label="Ventas e inventario">
            <article class="ui-card overflow-hidden">
                <div class="border-b border-stone-200 px-5 py-4">
                    <h2 class="text-lg font-bold">Productos más vendidos</h2>
                    <p class="mt-1 text-sm text-stone-600">Unidades de ventas confirmadas entre {{ $from }} y {{ $to }}.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-stone-200 text-left text-sm">
                        <thead class="bg-stone-50 text-xs uppercase tracking-wide text-stone-500"><tr><th class="px-5 py-3">Producto</th><th class="px-5 py-3 text-right">Unidades</th></tr></thead>
                        <tbody class="divide-y divide-stone-100">
                            @forelse ($dashboardMetrics['top_selling_products'] as $product)
                                <tr><td class="px-5 py-3 font-medium">{{ $product->product?->name ?? 'Producto no disponible' }}</td><td class="px-5 py-3 text-right tabular-nums">{{ number_format((int) $product->units_sold) }}</td></tr>
                            @empty
                                <tr><td colspan="2" class="px-5 py-6 text-center text-stone-500">Sin ventas confirmadas en el período.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="ui-card overflow-hidden">
                <div class="border-b border-stone-200 px-5 py-4">
                    <h2 class="text-lg font-bold">Inventario que requiere atención</h2>
                    <p class="mt-1 text-sm text-stone-600">Bajo mínimo: {{ $dashboardMetrics['inventory']['low_stock'] }} · Agotados: {{ $dashboardMetrics['inventory']['out_of_stock'] }}</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-stone-200 text-left text-sm">
                        <thead class="bg-stone-50 text-xs uppercase tracking-wide text-stone-500"><tr><th class="px-5 py-3">Producto</th><th class="px-5 py-3 text-right">Existencia</th><th class="px-5 py-3 text-right">Mínimo</th></tr></thead>
                        <tbody class="divide-y divide-stone-100">
                            @forelse ($dashboardMetrics['inventory']['low_stock_products'] as $inventory)
                                <tr><td class="px-5 py-3 font-medium">{{ $inventory->product?->name ?? 'Producto no disponible' }}</td><td class="px-5 py-3 text-right tabular-nums">{{ $inventory->stock }}</td><td class="px-5 py-3 text-right tabular-nums">{{ $inventory->minimum_stock }}</td></tr>
                            @empty
                                <tr><td colspan="3" class="px-5 py-6 text-center text-stone-500">No hay productos agotados o bajo mínimo.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>
        </section>

        <section class="grid gap-6 xl:grid-cols-3" aria-label="Operación, marketing y recursos humanos">
            <article class="ui-card p-5">
                <h2 class="text-lg font-bold">Pedidos por estado</h2>
                <dl class="mt-4 divide-y divide-stone-100">
                    @foreach (\App\Enums\OrderStatus::cases() as $status)
                        <div class="flex justify-between gap-4 py-2 text-sm"><dt class="text-stone-600">{{ $status->label() }}</dt><dd class="font-semibold tabular-nums">{{ number_format($dashboardMetrics['order_statuses'][$status->value] ?? 0) }}</dd></div>
                    @endforeach
                </dl>
            </article>

            <article class="ui-card p-5">
                <h2 class="text-lg font-bold">Marketing y e-commerce</h2>
                <dl class="mt-4 divide-y divide-stone-100">
                    @foreach ([
                        ['Visitas', number_format($dashboardMetrics['marketing']['visits'])],
                        ['Carritos iniciados', number_format($dashboardMetrics['marketing']['carts_started'])],
                        ['Checkouts iniciados', number_format($dashboardMetrics['marketing']['checkouts_started'])],
                        ['Pedidos web completados', number_format($dashboardMetrics['marketing']['orders_completed'])],
                        ['Conversión', number_format($dashboardMetrics['marketing']['conversion_rate'], 1).'%'],
                        ['Abandono', number_format($dashboardMetrics['marketing']['abandonment_rate'], 1).'%'],
                    ] as [$label, $value])
                        <div class="flex justify-between gap-4 py-2 text-sm"><dt class="text-stone-600">{{ $label }}</dt><dd class="font-semibold tabular-nums">{{ $value }}</dd></div>
                    @endforeach
                </dl>
            </article>

            <article class="ui-card p-5">
                <h2 class="text-lg font-bold">Compras y logística</h2>
                <p class="mt-3 text-sm text-stone-600">{{ number_format($dashboardMetrics['purchases']['count']) }} órdenes · Q {{ number_format((float) $dashboardMetrics['purchases']['active_total'], 2) }} pendientes o recibidas</p>
                <p class="mt-1 text-xs text-stone-500">El importe de órdenes no reemplaza los gastos registrados en Finanzas.</p>
                <dl class="mt-4 divide-y divide-stone-100">
                    @foreach (\App\Enums\OrderStatus::logisticsStages() as $status)
                        <div class="flex justify-between gap-4 py-2 text-sm"><dt class="text-stone-600">{{ $status->label() }}</dt><dd class="font-semibold tabular-nums">{{ number_format($dashboardMetrics['logistics']['stages'][$status->value] ?? 0) }}</dd></div>
                    @endforeach
                    <div class="flex justify-between gap-4 py-2 text-sm"><dt class="text-stone-600">Retrasados</dt><dd class="font-semibold tabular-nums">{{ number_format($dashboardMetrics['logistics']['delayed']) }}</dd></div>
                </dl>
                <p class="mt-3 text-xs text-stone-500">Tiempo promedio de entrega:
                    @if ($dashboardMetrics['logistics']['average_delivery_hours'] !== null)
                        {{ number_format($dashboardMetrics['logistics']['average_delivery_hours'], 1) }} horas (con despacho y entrega registrados)
                    @else
                        Sin datos suficientes
                    @endif
                </p>
            </article>
        </section>

        <section class="ui-card p-5" aria-label="Recursos humanos">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h2 class="text-lg font-bold">Recursos humanos</h2>
                <p class="text-sm text-stone-600">{{ number_format($dashboardMetrics['human_resources']['active_employees']) }} empleados activos · {{ number_format($dashboardMetrics['human_resources']['occupied_positions']) }} puestos ocupados</p>
            </div>
            <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @forelse ($dashboardMetrics['human_resources']['employees_by_department'] as $department)
                    <div class="rounded-lg bg-stone-50 p-4"><p class="text-sm font-medium text-stone-700">{{ $department->name }}</p><p class="mt-1 text-2xl font-bold tabular-nums">{{ number_format((int) $department->active_employees) }}</p><p class="text-xs text-stone-500">Empleados activos</p></div>
                @empty
                    <p class="text-sm text-stone-500">No hay departamentos registrados.</p>
                @endforelse
            </div>
        </section>
    @elseif ($isDentalDashboard)
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3" aria-label="Resumen del consultorio">
            @foreach ([
                ['label' => 'Total de pacientes', 'value' => $totalPatients, 'eyebrow' => 'Pacientes'],
                ['label' => 'Pacientes activos', 'value' => $activePatients, 'eyebrow' => 'Pacientes'],
                ['label' => 'Odontólogos activos', 'value' => $totalDentists, 'eyebrow' => 'Equipo clínico'],
                ['label' => 'Total de usuarios', 'value' => $totalUsers, 'eyebrow' => 'Accesos'],
                ['label' => 'Usuarios activos', 'value' => $activeUsers, 'eyebrow' => 'Accesos'],
            ] as $metric)
                <article class="ui-card relative overflow-hidden p-6">
                    <span class="absolute inset-y-0 left-0 w-1 bg-brand-600" aria-hidden="true"></span>
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-[0.68rem] font-bold uppercase tracking-[0.16em] text-brand-700">{{ $metric['eyebrow'] }}</p>
                            <p class="mt-2 text-sm font-medium text-stone-600">{{ $metric['label'] }}</p>
                        </div>
                        <span class="size-2 rounded-full bg-amber-400 ring-4 ring-amber-100" aria-hidden="true"></span>
                    </div>
                    <p class="mt-5 text-4xl font-bold tracking-tight text-stone-950">{{ $metric['value'] }}</p>
                </article>
            @endforeach
        </section>
    @else
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Indicadores generales">
            @foreach ([
                ['label' => 'Total de usuarios', 'value' => $totalUsers, 'eyebrow' => 'Accesos'],
                ['label' => 'Usuarios activos', 'value' => $activeUsers, 'eyebrow' => 'Accesos'],
                ['label' => 'Total de empleados', 'value' => $totalEmployees, 'eyebrow' => 'Equipo'],
                ['label' => 'Empleados activos', 'value' => $activeEmployees, 'eyebrow' => 'Equipo'],
            ] as $metric)
                <article class="ui-card relative overflow-hidden p-6">
                    <span class="absolute inset-y-0 left-0 w-1 bg-brand-600" aria-hidden="true"></span>
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-[0.68rem] font-bold uppercase tracking-[0.16em] text-brand-700">{{ $metric['eyebrow'] }}</p>
                            <p class="mt-2 text-sm font-medium text-stone-600">{{ $metric['label'] }}</p>
                        </div>
                        <span class="size-2 rounded-full bg-amber-400 ring-4 ring-amber-100" aria-hidden="true"></span>
                    </div>
                    <p class="mt-5 text-4xl font-bold tracking-tight text-stone-950">{{ $metric['value'] }}</p>
                </article>
            @endforeach
        </section>
    @endif
@endsection
