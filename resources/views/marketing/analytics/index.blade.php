@extends('layouts.admin')

@section('title', 'Analítica')
@section('heading', 'Marketing')
@section('subheading', 'Rendimiento del portal y del e-commerce')

@section('content')
    <form method="GET" action="{{ route('marketing.analytics.index') }}" class="ui-card flex flex-col gap-4 p-5 sm:flex-row sm:items-end">
        <div class="grid flex-1 gap-4 sm:grid-cols-2">
            <label class="grid gap-2 text-sm font-semibold text-stone-700">
                Fecha desde
                <input type="date" name="date_from" value="{{ $dateFrom->toDateString() }}" required class="min-h-11 rounded-lg border border-stone-300 bg-white px-3 text-stone-900 shadow-sm focus:border-brand-600 focus:outline-2 focus:outline-brand-600">
            </label>
            <label class="grid gap-2 text-sm font-semibold text-stone-700">
                Fecha hasta
                <input type="date" name="date_to" value="{{ $dateTo->toDateString() }}" required class="min-h-11 rounded-lg border border-stone-300 bg-white px-3 text-stone-900 shadow-sm focus:border-brand-600 focus:outline-2 focus:outline-brand-600">
            </label>
        </div>
        <button type="submit" class="ui-button ui-button-primary">Filtrar período</button>
    </form>

    <p class="mt-4 text-sm leading-6 text-stone-600">
        El abandono se calcula como carritos iniciados menos pedidos web completados, dividido entre carritos iniciados; el resultado mínimo es 0%.
        Un pedido cuenta al persistirse el checkout web; los productos más vendidos se calculan desde ventas confirmadas.
        Las recargas consecutivas de una ficha de producto dentro de la misma sesión no duplican su visualización.
    </p>

    <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Indicadores de marketing">
        @foreach ([
            ['label' => 'Visitas', 'value' => number_format($metrics['visits']), 'description' => 'Sesiones únicas en el portal'],
            ['label' => 'Productos vistos', 'value' => number_format($metrics['products_viewed']), 'description' => 'Visualizaciones de fichas'],
            ['label' => 'Carritos iniciados', 'value' => number_format($metrics['carts_started']), 'description' => 'Nuevos carritos con productos'],
            ['label' => 'Checkouts iniciados', 'value' => number_format($metrics['checkouts_started']), 'description' => 'Sesiones que abrieron checkout'],
            ['label' => 'Pedidos completados', 'value' => number_format($metrics['orders_completed']), 'description' => 'Pedidos web persistidos'],
            ['label' => 'Conversión', 'value' => number_format($metrics['conversion_rate'], 1).'%', 'description' => 'Pedidos completados / visitas'],
            ['label' => 'Abandono del carrito', 'value' => number_format($metrics['abandonment_rate'], 1).'%', 'description' => 'Carritos sin pedido completado'],
        ] as $metric)
            <article class="ui-card relative overflow-hidden p-5">
                <span class="absolute inset-y-0 left-0 w-1 bg-brand-600" aria-hidden="true"></span>
                <p class="text-sm font-semibold text-stone-600">{{ $metric['label'] }}</p>
                <p class="mt-3 text-3xl font-bold tracking-tight text-stone-950">{{ $metric['value'] }}</p>
                <p class="mt-2 text-xs text-stone-500">{{ $metric['description'] }}</p>
            </article>
        @endforeach
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-2" aria-label="Rendimiento de productos">
        <article class="ui-card overflow-hidden">
            <div class="border-b border-stone-200 px-6 py-5">
                <h2 class="text-lg font-bold text-stone-950">Productos más vistos</h2>
                <p class="mt-1 text-sm text-stone-600">Fichas consultadas durante el período.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-stone-200 text-left text-sm">
                    <thead class="bg-stone-50 text-xs uppercase tracking-wide text-stone-500">
                        <tr>
                            <th scope="col" class="px-6 py-3 font-semibold">Producto</th>
                            <th scope="col" class="px-6 py-3 text-right font-semibold">Vistas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 text-stone-700">
                        @forelse ($topViewedProducts as $event)
                            <tr>
                                <td class="px-6 py-4 font-medium">{{ $event->product?->name ?? 'Producto no disponible' }}</td>
                                <td class="px-6 py-4 text-right tabular-nums">{{ number_format((int) $event->total_views) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="px-6 py-8 text-center text-stone-500">No hay visualizaciones en este período.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <article class="ui-card overflow-hidden">
            <div class="border-b border-stone-200 px-6 py-5">
                <h2 class="text-lg font-bold text-stone-950">Productos más vendidos</h2>
                <p class="mt-1 text-sm text-stone-600">Unidades de ventas confirmadas durante el período.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-stone-200 text-left text-sm">
                    <thead class="bg-stone-50 text-xs uppercase tracking-wide text-stone-500">
                        <tr>
                            <th scope="col" class="px-6 py-3 font-semibold">Producto</th>
                            <th scope="col" class="px-6 py-3 text-right font-semibold">Unidades</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 text-stone-700">
                        @forelse ($topSellingProducts as $detail)
                            <tr>
                                <td class="px-6 py-4 font-medium">{{ $detail->product?->name ?? 'Producto no disponible' }}</td>
                                <td class="px-6 py-4 text-right tabular-nums">{{ number_format((int) $detail->units_sold) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="px-6 py-8 text-center text-stone-500">No hay ventas confirmadas en este período.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </section>
@endsection
