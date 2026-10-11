<div data-purchase-line class="grid gap-4 rounded-lg border border-stone-200 p-4 sm:grid-cols-2 lg:grid-cols-[minmax(0,2fr)_minmax(7rem,1fr)_minmax(9rem,1fr)_minmax(7rem,1fr)_auto]">
    <div>
        <label data-purchase-label="product_id" for="detail-{{ $index }}-product_id" class="block text-sm font-medium">Producto</label>
        <select id="detail-{{ $index }}-product_id" name="details[{{ $index }}][product_id]" data-purchase-field="product_id" required
            class="mt-2 w-full rounded-lg border border-stone-300 bg-white px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
            <option value="">Selecciona un producto</option>
            @foreach ($products as $product)
                <option value="{{ $product->id }}" @selected((string) ($detail['product_id'] ?? '') === (string) $product->id)>
                    {{ $product->sku }} — {{ $product->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label data-purchase-label="quantity" for="detail-{{ $index }}-quantity" class="block text-sm font-medium">Cantidad</label>
        <input id="detail-{{ $index }}-quantity" name="details[{{ $index }}][quantity]" data-purchase-field="quantity"
            type="number" min="1" max="2147483647" step="1" value="{{ $detail['quantity'] ?? '' }}" required
            class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 tabular-nums focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
    </div>

    <div>
        <label data-purchase-label="unit_cost" for="detail-{{ $index }}-unit_cost" class="block text-sm font-medium">Costo unitario</label>
        <input id="detail-{{ $index }}-unit_cost" name="details[{{ $index }}][unit_cost]" data-purchase-field="unit_cost"
            type="number" min="0" max="9999999999.99" step="0.01" value="{{ $detail['unit_cost'] ?? '' }}" required
            class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 tabular-nums focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
    </div>

    <div>
        <span class="block text-sm font-medium">Subtotal</span>
        <output data-purchase-subtotal class="mt-2 block rounded-lg bg-stone-50 px-3 py-2 text-right font-semibold tabular-nums">
            {{ number_format((float) ($detail['subtotal'] ?? 0), 2) }}
        </output>
    </div>

    <div class="flex items-end">
        <button type="button" data-remove-purchase-line class="w-full rounded-lg border border-red-200 px-3 py-2 text-sm font-semibold text-red-800 hover:bg-red-50">
            Quitar
        </button>
    </div>
</div>
