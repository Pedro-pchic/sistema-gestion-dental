<div data-order-line class="grid gap-3 rounded-lg border border-stone-200 p-4 sm:grid-cols-2 lg:grid-cols-[minmax(0,2fr)_minmax(8rem,1fr)_minmax(9rem,1fr)_minmax(8rem,1fr)_auto]">
    <div>
        <label data-order-label="product_id" for="order-detail-{{ $index }}-product-id" class="block text-sm font-medium">Producto</label>
        <select data-order-field="product_id" id="order-detail-{{ $index }}-product-id" name="details[{{ $index }}][product_id]" required
            class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
            <option value="">Selecciona un producto</option>
            @foreach ($products as $productOption)
                <option value="{{ $productOption->id }}" data-price="{{ $productOption->price }}" @selected((string) ($detail['product_id'] ?? '') === (string) $productOption->id)>
                    {{ $productOption->sku }} — {{ $productOption->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <label data-order-label="quantity" for="order-detail-{{ $index }}-quantity" class="block text-sm font-medium">Cantidad</label>
        <input data-order-field="quantity" id="order-detail-{{ $index }}-quantity" name="details[{{ $index }}][quantity]" type="number" min="1" max="2147483647" value="{{ $detail['quantity'] ?? 1 }}" required
            class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
    </div>
    <div>
        <label data-order-label="unit_price" for="order-detail-{{ $index }}-unit-price" class="block text-sm font-medium">Precio unitario</label>
        <input data-order-field="unit_price" id="order-detail-{{ $index }}-unit-price" name="details[{{ $index }}][unit_price]" type="number" min="0" max="9999999999.99" step="0.01" value="{{ $detail['unit_price'] ?? '' }}" required
            class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
    </div>
    <div class="self-end pb-2 text-sm text-stone-600">Subtotal: <span data-order-subtotal class="font-semibold tabular-nums">Q 0.00</span></div>
    <button type="button" data-remove-order-line class="self-end rounded-lg border border-stone-300 px-3 py-2 text-sm font-semibold hover:bg-stone-50">Quitar</button>
</div>
