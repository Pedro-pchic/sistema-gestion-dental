<div class="grid gap-5 sm:grid-cols-2">
    <div>
        <label for="category_id" class="block text-sm font-medium">Categoría</label>
        <select id="category_id" name="category_id" required
            class="mt-2 w-full rounded-lg border border-stone-300 bg-white px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
            <option value="">Selecciona una categoría</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((string) old('category_id', $product->category_id ?? '') === (string) $category->id)>
                    {{ $category->name }}{{ $category->is_active ? '' : ' — Inactiva' }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="sku" class="block text-sm font-medium">SKU</label>
        <input id="sku" name="sku" type="text" value="{{ old('sku', $product->sku ?? '') }}" required
            class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
    </div>

    <div>
        <label for="name" class="block text-sm font-medium">Nombre</label>
        <input id="name" name="name" type="text" value="{{ old('name', $product->name ?? '') }}" required
            class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
    </div>

    <div>
        <label for="price" class="block text-sm font-medium">Precio</label>
        <input id="price" name="price" type="number" min="0" max="9999999999.99" step="0.01" value="{{ old('price', $product->price ?? '') }}" required
            class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
    </div>

    <div class="sm:col-span-2">
        <label for="description" class="block text-sm font-medium">Descripción</label>
        <textarea id="description" name="description" rows="4"
            class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">{{ old('description', $product->description ?? '') }}</textarea>
    </div>

    <div class="sm:col-span-2">
        <label for="image" class="block text-sm font-medium">Fotografía del producto</label>
        <p class="mt-1 text-sm text-stone-600">JPG, JPEG, PNG o WebP. Máximo 2 MB.</p>
        <input id="image" name="image" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
            data-product-image-input
            class="mt-3 block w-full rounded-lg border border-stone-300 bg-white text-sm file:mr-4 file:border-0 file:bg-stone-100 file:px-4 file:py-2 file:font-semibold file:text-stone-800 hover:file:bg-stone-200">
        @error('image')
            <p class="mt-2 text-sm font-medium text-red-700">{{ $message }}</p>
        @enderror

        <div class="mt-4 flex flex-wrap items-center gap-4">
            @if (isset($product))
                <x-product-image :product="$product" width="240" class="h-28 w-28 shrink-0 rounded-lg" data-product-image-current />
            @else
                <div aria-hidden="true" class="grid h-28 w-28 shrink-0 place-items-center rounded-lg bg-brand-50 text-brand-800" data-product-image-current>
                    <span class="text-3xl font-black tracking-tight">{{ mb_strtoupper(mb_substr(old('name', 'PR'), 0, 2)) }}</span>
                </div>
            @endif
            <img src="" alt="Vista previa de la fotografía seleccionada" width="240" height="180" loading="lazy" decoding="async"
                class="hidden h-28 w-28 shrink-0 rounded-lg object-cover" data-product-image-preview>
            @if (isset($product) && $product->image_public_id)
                <label class="flex items-center gap-2 text-sm font-medium">
                    <input id="remove_image" name="remove_image" type="checkbox" value="1" @checked((bool) old('remove_image'))
                        class="rounded border-stone-300 text-brand-600 focus:ring-brand-500">
                    Eliminar fotografía actual
                </label>
            @endif
        </div>
    </div>

    <div class="sm:col-span-2">
        <input type="hidden" name="is_active" value="0">
        <label class="flex items-center gap-2 text-sm font-medium">
            <input name="is_active" type="checkbox" value="1" @checked((bool) old('is_active', $product->is_active ?? true))
                class="rounded border-stone-300 text-brand-600 focus:ring-brand-500">
            Producto activo
        </label>
    </div>
</div>
