@extends('layouts.admin')

@section('title', 'Movimientos de inventario')
@section('heading', 'Movimientos de inventario')
@section('subheading', 'Registra entradas, salidas y ajustes; el historial no se puede editar ni eliminar')

@section('content')
    <form method="POST" action="{{ route('inventario.movimientos.store') }}" class="grid gap-5 rounded-xl bg-white p-6 shadow-sm ring-1 ring-stone-200 sm:grid-cols-2 lg:grid-cols-4">
        @csrf
        <div>
            <label for="product_id" class="block text-sm font-medium">Producto</label>
            <select id="product_id" name="product_id" required
                class="mt-2 w-full rounded-lg border border-stone-300 bg-white px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
                <option value="">Selecciona un producto</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}" @selected((string) old('product_id', $productId ?? '') === (string) $product->id)>
                        {{ $product->sku }} — {{ $product->name }} (stock: {{ $product->inventory?->stock ?? 0 }})
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="type" class="block text-sm font-medium">Tipo de movimiento</label>
            <select id="type" name="type" required
                class="mt-2 w-full rounded-lg border border-stone-300 bg-white px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
                @foreach ($movementTypes as $movementType)
                    <option value="{{ $movementType->value }}" @selected(old('type', 'entry') === $movementType->value)>
                        {{ $movementType->label() }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="quantity" class="block text-sm font-medium">Cantidad o existencia resultante</label>
            <input id="quantity" name="quantity" type="number" min="0" step="1" value="{{ old('quantity') }}" required
                class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
            <p class="mt-1 text-xs text-stone-500">En un ajuste, indica el nuevo stock; el historial guardará la diferencia.</p>
        </div>

        <div>
            <label for="reason" class="block text-sm font-medium">Motivo <span class="text-stone-500">(obligatorio en ajustes)</span></label>
            <input id="reason" name="reason" type="text" maxlength="500" value="{{ old('reason') }}"
                class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
        </div>

        <div class="sm:col-span-2 lg:col-span-4">
            <button type="submit" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600">Registrar movimiento</button>
        </div>
    </form>

    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-stone-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200 text-sm">
                <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-600">
                    <tr>
                        <th class="px-4 py-3">Fecha</th>
                        <th class="px-4 py-3">Producto</th>
                        <th class="px-4 py-3">Tipo</th>
                        <th class="px-4 py-3 text-right">Cantidad</th>
                        <th class="px-4 py-3 text-right">Stock anterior</th>
                        <th class="px-4 py-3 text-right">Stock resultante</th>
                        <th class="px-4 py-3">Usuario</th>
                        <th class="px-4 py-3">Motivo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($movements as $movement)
                        <tr>
                            <td class="whitespace-nowrap px-4 py-3 text-stone-600">{{ $movement->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="font-medium">{{ $movement->inventory->product->name }}</span>
                                <span class="mt-0.5 block font-mono text-xs text-stone-500">{{ $movement->inventory->product->sku }}</span>
                            </td>
                            <td class="px-4 py-3">{{ $movement->type->label() }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">
                                @if ($movement->type === \App\Enums\InventoryMovementType::Entry)
                                    +{{ number_format($movement->quantity) }}
                                @elseif ($movement->type === \App\Enums\InventoryMovementType::Exit)
                                    −{{ number_format($movement->quantity) }}
                                @elseif ($movement->quantity > 0)
                                    +{{ number_format($movement->quantity) }}
                                @else
                                    {{ number_format($movement->quantity) }}
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ number_format($movement->previous_stock) }}</td>
                            <td class="px-4 py-3 text-right font-semibold tabular-nums">{{ number_format($movement->resulting_stock) }}</td>
                            <td class="px-4 py-3">{{ $movement->user?->name ?? 'Sistema' }}</td>
                            <td class="max-w-xs px-4 py-3 text-stone-600">{{ $movement->reason ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-stone-500">Todavía no hay movimientos registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($movements->hasPages())
            <div class="border-t border-stone-200 px-4 py-3">{{ $movements->links() }}</div>
        @endif
    </div>
@endsection
