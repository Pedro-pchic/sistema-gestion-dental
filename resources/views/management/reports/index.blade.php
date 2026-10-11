@extends('layouts.admin')

@section('title', 'Reportes de gestión')
@section('heading', 'Reportes')
@section('subheading', 'Consultas operativas basadas en ventas, inventario, compras y logística')

@section('actions')
    <button type="button" onclick="window.print()" class="ui-button ui-button-secondary print:hidden">Imprimir</button>
@endsection

@section('content')
    <form method="GET" action="{{ route('gestion.reportes.index') }}" class="ui-card grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-5 print:hidden">
        <label class="grid gap-2 text-sm font-semibold text-stone-700">
            Fecha desde
            <input type="date" name="from" value="{{ $from }}" required class="min-h-11 rounded-lg border border-stone-300 bg-white px-3 text-stone-900 shadow-sm focus:border-brand-600 focus:outline-2 focus:outline-brand-600">
        </label>
        <label class="grid gap-2 text-sm font-semibold text-stone-700">
            Fecha hasta
            <input type="date" name="to" value="{{ $to }}" required class="min-h-11 rounded-lg border border-stone-300 bg-white px-3 text-stone-900 shadow-sm focus:border-brand-600 focus:outline-2 focus:outline-brand-600">
        </label>
        <label class="grid gap-2 text-sm font-semibold text-stone-700">
            Estado de venta
            <select name="sale_status" class="min-h-11 rounded-lg border border-stone-300 bg-white px-3 text-stone-900 shadow-sm focus:border-brand-600 focus:outline-2 focus:outline-brand-600">
                <option value="">Todos</option>
                @foreach ($saleStatuses as $status)
                    <option value="{{ $status->value }}" @selected(request('sale_status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </label>
        <label class="grid gap-2 text-sm font-semibold text-stone-700">
            Estado de compra
            <select name="purchase_status" class="min-h-11 rounded-lg border border-stone-300 bg-white px-3 text-stone-900 shadow-sm focus:border-brand-600 focus:outline-2 focus:outline-brand-600">
                <option value="">Todos</option>
                @foreach ($purchaseStatuses as $status)
                    <option value="{{ $status->value }}" @selected(request('purchase_status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </label>
        <label class="grid gap-2 text-sm font-semibold text-stone-700">
            Estado logístico
            <select name="logistics_status" class="min-h-11 rounded-lg border border-stone-300 bg-white px-3 text-stone-900 shadow-sm focus:border-brand-600 focus:outline-2 focus:outline-brand-600">
                <option value="">Todos</option>
                @foreach ($logisticsStatuses as $status)
                    <option value="{{ $status->value }}" @selected(request('logistics_status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </label>
        <div class="md:col-span-2 xl:col-span-5">
            <button type="submit" class="ui-button ui-button-primary">Aplicar filtros</button>
        </div>
    </form>

    <p class="text-sm text-stone-600">Período consultado: <strong>{{ $from }}</strong> al <strong>{{ $to }}</strong>. Las tablas muestran hasta 20 registros recientes por sección.</p>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Resumen de reportes">
        <article class="ui-card p-5">
            <p class="text-sm font-semibold text-stone-600">Ventas confirmadas</p>
            <p class="mt-2 text-3xl font-bold tabular-nums">{{ number_format($salesSummary['count']) }}</p>
            <p class="mt-1 text-sm text-stone-500">Q {{ number_format((float) $salesSummary['total'], 2) }} · ticket Q {{ number_format((float) $salesSummary['average_ticket'], 2) }}</p>
        </article>
        <article class="ui-card p-5">
            <p class="text-sm font-semibold text-stone-600">Órdenes de compra</p>
            <p class="mt-2 text-3xl font-bold tabular-nums">{{ number_format($purchasesSummary['count']) }}</p>
            <p class="mt-1 text-sm text-stone-500">Q {{ number_format((float) $purchasesSummary['active_total'], 2) }} pendientes o recibidas</p>
        </article>
        <article class="ui-card p-5">
            <p class="text-sm font-semibold text-stone-600">Productos bajo mínimo</p>
            <p class="mt-2 text-3xl font-bold tabular-nums">{{ number_format($inventorySummary['low_stock']) }}</p>
            <p class="mt-1 text-sm text-stone-500">Con existencia positiva</p>
        </article>
        <article class="ui-card p-5">
            <p class="text-sm font-semibold text-stone-600">Productos agotados</p>
            <p class="mt-2 text-3xl font-bold tabular-nums">{{ number_format($inventorySummary['out_of_stock']) }}</p>
            <p class="mt-1 text-sm text-stone-500">Existencia en cero</p>
        </article>
    </section>

    <section class="ui-card overflow-hidden" aria-labelledby="sales-report-title">
        <div class="border-b border-stone-200 px-5 py-4">
            <h2 id="sales-report-title" class="text-lg font-bold">Ventas</h2>
            <p class="mt-1 text-sm text-stone-600">Ventas confirmadas y canceladas; no se muestran datos personales de clientes.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200 text-left text-sm">
                <thead class="bg-stone-50 text-xs uppercase tracking-wide text-stone-500"><tr><th class="px-5 py-3">Número</th><th class="px-5 py-3">Fecha</th><th class="px-5 py-3">Estado</th><th class="px-5 py-3 text-right">Total</th></tr></thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($sales as $sale)
                        <tr><td class="px-5 py-3 font-medium">{{ $sale->number }}</td><td class="px-5 py-3">{{ $sale->sale_date->toDateString() }}</td><td class="px-5 py-3">{{ $sale->status->label() }}</td><td class="px-5 py-3 text-right tabular-nums">Q {{ number_format((float) $sale->total, 2) }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-7 text-center text-stone-500">No hay ventas para los filtros seleccionados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="ui-card overflow-hidden" aria-labelledby="inventory-report-title">
        <div class="border-b border-stone-200 px-5 py-4">
            <h2 id="inventory-report-title" class="text-lg font-bold">Inventario bajo</h2>
            <p class="mt-1 text-sm text-stone-600">Incluye productos agotados y productos con existencia positiva en o por debajo de su mínimo.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200 text-left text-sm">
                <thead class="bg-stone-50 text-xs uppercase tracking-wide text-stone-500"><tr><th class="px-5 py-3">Producto</th><th class="px-5 py-3">SKU</th><th class="px-5 py-3">Estado</th><th class="px-5 py-3 text-right">Existencia</th><th class="px-5 py-3 text-right">Mínimo</th></tr></thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($lowStockProducts as $inventory)
                        <tr><td class="px-5 py-3 font-medium">{{ $inventory->product?->name ?? 'Producto no disponible' }}</td><td class="px-5 py-3">{{ $inventory->product?->sku ?? '—' }}</td><td class="px-5 py-3">{{ $inventory->stock === 0 ? 'Agotado' : 'Bajo mínimo' }}</td><td class="px-5 py-3 text-right tabular-nums">{{ $inventory->stock }}</td><td class="px-5 py-3 text-right tabular-nums">{{ $inventory->minimum_stock }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-7 text-center text-stone-500">No hay productos agotados o bajo mínimo.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="ui-card overflow-hidden" aria-labelledby="purchases-report-title">
        <div class="border-b border-stone-200 px-5 py-4">
            <h2 id="purchases-report-title" class="text-lg font-bold">Compras</h2>
            <p class="mt-1 text-sm text-stone-600">El importe considera órdenes pendientes o recibidas; no equivale automáticamente a gasto contable.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200 text-left text-sm">
                <thead class="bg-stone-50 text-xs uppercase tracking-wide text-stone-500"><tr><th class="px-5 py-3">Número</th><th class="px-5 py-3">Fecha</th><th class="px-5 py-3">Estado</th><th class="px-5 py-3 text-right">Total registrado</th></tr></thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($purchases as $purchase)
                        <tr><td class="px-5 py-3 font-medium">{{ $purchase->number }}</td><td class="px-5 py-3">{{ $purchase->order_date->toDateString() }}</td><td class="px-5 py-3">{{ $purchase->status->label() }}</td><td class="px-5 py-3 text-right tabular-nums">Q {{ number_format((float) $purchase->total, 2) }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-7 text-center text-stone-500">No hay compras para los filtros seleccionados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="ui-card overflow-hidden" aria-labelledby="logistics-report-title">
        <div class="border-b border-stone-200 px-5 py-4">
            <h2 id="logistics-report-title" class="text-lg font-bold">Pedidos y logística</h2>
            <p class="mt-1 text-sm text-stone-600">Solo pedidos completados vinculados a una venta confirmada, según las reglas operativas existentes.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200 text-left text-sm">
                <thead class="bg-stone-50 text-xs uppercase tracking-wide text-stone-500"><tr><th class="px-5 py-3">Pedido</th><th class="px-5 py-3">Fecha</th><th class="px-5 py-3">Estado</th><th class="px-5 py-3">Entrega estimada</th><th class="px-5 py-3">Seguimiento</th></tr></thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($logisticsOrders as $order)
                        <tr><td class="px-5 py-3 font-medium">{{ $order->number }}</td><td class="px-5 py-3">{{ $order->order_date->toDateString() }}</td><td class="px-5 py-3">{{ $order->currentStatus()->label() }}</td><td class="px-5 py-3">{{ $order->estimated_delivery_at?->format('Y-m-d H:i') ?? 'Sin fecha' }}</td><td class="px-5 py-3">{{ $order->isDelayed() ? 'Retrasado' : 'En plazo' }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-7 text-center text-stone-500">No hay pedidos logísticos para los filtros seleccionados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($canViewFinance)
        <section class="ui-card p-5" aria-labelledby="finance-report-title">
            <h2 id="finance-report-title" class="text-lg font-bold">Finanzas</h2>
            <p class="mt-1 text-sm text-stone-600">Ingresos y gastos del módulo financiero; los gastos conservan su clasificación independiente de las compras.</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                @foreach ([['Ingresos', $financialSummary['income']], ['Gastos', $financialSummary['expense']], ['Balance', $financialSummary['balance']]] as [$label, $amount])
                    <div class="rounded-lg bg-stone-50 p-4"><p class="text-sm text-stone-600">{{ $label }}</p><p class="mt-1 text-xl font-bold tabular-nums">Q {{ number_format((float) $amount, 2) }}</p></div>
                @endforeach
            </div>
            <div class="mt-5 grid gap-5 md:grid-cols-2">
                @foreach ([['Ingresos por categoría', $incomeCategories], ['Gastos por categoría', $expenseCategories]] as [$heading, $categories])
                    <div>
                        <h3 class="font-semibold">{{ $heading }}</h3>
                        <dl class="mt-2 divide-y divide-stone-100">
                            @forelse ($categories as $category)
                                <div class="flex justify-between gap-4 py-2 text-sm"><dt class="text-stone-600">{{ $category['category'] }}</dt><dd class="font-medium tabular-nums">Q {{ number_format((float) $category['total'], 2) }}</dd></div>
                            @empty
                                <p class="py-2 text-sm text-stone-500">Sin movimientos en el período.</p>
                            @endforelse
                        </dl>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
@endsection
