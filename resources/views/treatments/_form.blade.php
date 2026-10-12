<div>
    <!-- Well begun is half done. - Aristotle -->
</div>
<div class="grid gap-5 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label for="name" class="block text-sm font-medium">Nombre del tratamiento</label>
        <input id="name" name="name" type="text" value="{{ old('name', $treatment->name ?? '') }}" required maxlength="160" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
    </div>
    <div>
        <label for="price" class="block text-sm font-medium">Precio (Q)</label>
        <input id="price" name="price" type="number" min="0" max="99999999.99" step="0.01" value="{{ old('price', $treatment->price ?? '') }}" required class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
    </div>
    <div>
        <label for="estimated_duration_minutes" class="block text-sm font-medium">Duración estimada (minutos)</label>
        <input id="estimated_duration_minutes" name="estimated_duration_minutes" type="number" min="1" max="32767" step="1" value="{{ old('estimated_duration_minutes', $treatment->estimated_duration_minutes ?? '') }}" required class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
    </div>
    <div class="sm:col-span-2">
        <label for="description" class="block text-sm font-medium">Descripción</label>
        <textarea id="description" name="description" rows="4" maxlength="10000" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">{{ old('description', $treatment->description ?? '') }}</textarea>
    </div>
    <div class="sm:col-span-2">
        <input type="hidden" name="is_active" value="0">
        <label class="flex items-center gap-2 text-sm font-medium">
            <input name="is_active" type="checkbox" value="1" @checked((bool) old('is_active', $treatment->is_active ?? true)) class="rounded border-stone-300 text-brand-600 focus:ring-brand-500">
            Tratamiento activo
        </label>
    </div>
</div>
