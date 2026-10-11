@extends('layouts.admin')

@section('title', 'Existencias')
@section('heading', 'Existencias')
@section('subheading', 'Inventario actual por producto')

@section('actions')
    <a href="{{ route('inventario.movimientos.index') }}" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600">Registrar movimiento</a>
@endsection

@section('content')
    <form method="GET" action="{{ route('inventario.existencias') }}" class="flex flex-wrap items-end gap-3 rounded-xl bg-white p-4 shadow-sm ring-1 ring-stone-200">
        <div class="min-w-64 flex-1">
            <label for="search" class="block text-sm font-medium">Buscar por SKU, producto o categoría</label>
            <input id="search" name="search" type="search" value="{{ $search }}" maxlength="100"
                class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
        </div>
        <button type="submit" class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold hover:bg-stone-50">Buscar</button>
        @if ($search !== '')
            <a href="{{ route('inventario.existencias') }}" class="rounded-lg px-4 py-2 text-sm font-semibold text-stone-600 hover:bg-stone-100">Limpiar</a>
        @endif
    </form>

    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-stone-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200 text-sm">
                <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-600">
                    <tr>
                        <th class="px-4 py-3">SKU</th>
                        <th class="px-4 py-3">Producto</th>
                        <th class="px-4 py-3">Categoría</th>
                        <th class="px-4 py-3 text-right">Stock actual</th>
                        <th class="px-4 py-3">Stock mínimo</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($inventories as $inventory)
                        <tr>
                            <td class="px-4 py-3 font-mono text-xs font-semibold">{{ $inventory->product->sku }}</td>
                            <td class="px-4 py-3 font-medium">{{ $inventory->product->name }}</td>
                            <td class="px-4 py-3 text-stone-600">{{ $inventory->product->category->name }}</td>
                            <td class="px-4 py-3 text-right font-semibold tabular-nums">{{ number_format($inventory->stock) }}</td>
                            <td class="px-4 py-3">
                                <form method="POST" action="{{ route('inventario.minimum-stock', $inventory) }}" class="flex items-center gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <label for="minimum-stock-{{ $inventory->id }}" class="sr-only">Stock mínimo para {{ $inventory->product->name }}</label>
                                    <input id="minimum-stock-{{ $inventory->id }}" name="minimum_stock" type="number" min="0" step="1" required
                                        value="{{ $inventory->minimum_stock }}"
                                        class="w-24 rounded-lg border border-stone-300 px-2 py-1.5 text-right tabular-nums focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
                                    <button type="submit" class="rounded-md border border-stone-300 px-2.5 py-1.5 text-xs font-semibold hover:bg-stone-50">Guardar</button>
                                </form>
                            </td>
                            <td class="px-4 py-3">
                                @if ($inventory->stock === 0)
                                    <span class="rounded-full bg-stone-200 px-2.5 py-1 text-xs font-semibold text-stone-700">Sin stock</span>
                                @elseif ($inventory->stock <= $inventory->minimum_stock)
                                    <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-900">Stock bajo</span>
                                @else
                                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800">Disponible</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('inventario.movimientos.index', ['product_id' => $inventory->product_id]) }}"
                                    class="inline-flex rounded-md border border-stone-300 px-3 py-1.5 font-medium hover:bg-stone-50">Ver movimientos</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-stone-500">No hay existencias que coincidan con la búsqueda.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($inventories->hasPages())
            <div class="border-t border-stone-200 px-4 py-3">{{ $inventories->links() }}</div>
        @endif
    </div>
@endsection
