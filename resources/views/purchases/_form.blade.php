<form method="POST" action="{{ $purchase ? route('compras.update', $purchase) : route('compras.store') }}"
    data-purchase-form class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
    @csrf
    @if ($purchase)
        @method('PUT')
    @endif

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="supplier_id" class="block text-sm font-medium">Proveedor</label>
            <select id="supplier_id" name="supplier_id" required
                class="mt-2 w-full rounded-lg border border-stone-300 bg-white px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
                <option value="">Selecciona un proveedor activo</option>
                @foreach ($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" @selected((string) old('supplier_id', $purchase?->supplier_id ?? '') === (string) $supplier->id)>
                        {{ $supplier->name }}{{ $supplier->nit ? ' — '.$supplier->nit : '' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="order_date" class="block text-sm font-medium">Fecha de orden</label>
            <input id="order_date" name="order_date" type="date"
                value="{{ old('order_date', $purchase?->order_date?->format('Y-m-d') ?? now()->toDateString()) }}" required
                class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
        </div>

        <div class="sm:col-span-2">
            <label for="notes" class="block text-sm font-medium">Notas</label>
            <textarea id="notes" name="notes" rows="3" maxlength="5000"
                class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">{{ old('notes', $purchase?->notes ?? '') }}</textarea>
        </div>
    </div>

    <div class="mt-6 border-t border-stone-200 pt-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold">Productos de la orden</h2>
                <p class="mt-1 text-sm text-stone-600">La cantidad y el costo se validan y totalizan nuevamente en el servidor.</p>
            </div>
            <button type="button" data-add-purchase-line class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold hover:bg-stone-50">
                Agregar producto
            </button>
        </div>

        <div data-purchase-lines class="mt-4 flex flex-col gap-3">
            @forelse (old('details', $details) as $index => $detail)
                @include('purchases._detail-row', ['detail' => $detail, 'index' => $index])
            @empty
                @include('purchases._detail-row', ['detail' => [], 'index' => 0])
            @endforelse
        </div>

        <template data-purchase-line-template>
            @include('purchases._detail-row', ['detail' => [], 'index' => '__INDEX__'])
        </template>

        <div class="mt-5 flex justify-end border-t border-stone-200 pt-4">
            <p class="text-right text-lg font-bold">
                Total: <output data-purchase-total>{{ number_format((float) ($purchase?->total ?? 0), 2) }}</output>
            </p>
        </div>
    </div>

    <div class="mt-6 flex justify-end gap-3 border-t border-stone-200 pt-5">
        <a href="{{ $purchase ? route('compras.show', $purchase) : route('compras.index') }}"
            class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold hover:bg-stone-50">Cancelar</a>
        <button type="submit" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600">
            {{ $purchase ? 'Guardar borrador' : 'Crear borrador' }}
        </button>
    </div>
</form>
