@php
    $selectedProductId = (string) ($detail['product_id'] ?? '');
    $rowIndex = $index === '__INDEX__' ? '__INDEX__' : (int) $index;
@endphp

<div data-quote-line class="grid gap-4 rounded-lg border border-stone-200 p-4 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_minmax(0,1fr)_auto] lg:items-end">
    <div>
        <label data-quote-label="product_id" for="quote-product-{{ $rowIndex }}" class="block text-sm font-medium">Producto</label>
        <select data-quote-field="product_id" id="quote-product-{{ $rowIndex }}" name="details[{{ $rowIndex }}][product_id]" required
            class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
            <option value="">Selecciona un producto</option>
            @foreach ($products as $product)
                <option value="{{ $product->id }}" data-price="{{ $product->price }}" @selected((string) $product->id === $selectedProductId)>
                    {{ $product->sku }} — {{ $product->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label data-quote-label="quantity" for="quote-quantity-{{ $rowIndex }}" class="block text-sm font-medium">Cantidad</label>
        <input data-quote-field="quantity" id="quote-quantity-{{ $rowIndex }}" name="details[{{ $rowIndex }}][quantity]" type="number" min="1" max="2147483647" value="{{ $detail['quantity'] ?? 1 }}" required
            class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
    </div>

    <div>
        <label data-quote-label="unit_price" for="quote-unit-price-{{ $rowIndex }}" class="block text-sm font-medium">Precio unitario (Q)</label>
        <input data-quote-field="unit_price" id="quote-unit-price-{{ $rowIndex }}" name="details[{{ $rowIndex }}][unit_price]" type="number" min="0" max="9999999999.99" step="0.01" value="{{ $detail['unit_price'] ?? '' }}" required
            class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
        <p class="mt-1 text-xs text-stone-500">El valor se guarda en la cotización como precio histórico.</p>
    </div>

    <div class="flex items-end justify-between gap-4 lg:block lg:text-right">
        <div>
            <span class="block text-sm font-medium">Subtotal</span>
            <span data-quote-subtotal class="mt-2 block py-2 font-semibold tabular-nums">Q 0.00</span>
        </div>
        <button type="button" data-remove-quote-line class="rounded-lg border border-red-200 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">Quitar</button>
    </div>
</div>
