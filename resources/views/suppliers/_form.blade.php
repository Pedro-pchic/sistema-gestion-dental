<div class="grid gap-5 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label for="name" class="block text-sm font-medium">Nombre del proveedor</label>
        <input id="name" name="name" type="text" maxlength="255" value="{{ old('name', $supplier->name ?? '') }}" required
            class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
    </div>

    <div>
        <label for="nit" class="block text-sm font-medium">NIT</label>
        <input id="nit" name="nit" type="text" maxlength="50" value="{{ old('nit', $supplier->nit ?? '') }}"
            class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
    </div>

    <div>
        <label for="contact_name" class="block text-sm font-medium">Persona de contacto</label>
        <input id="contact_name" name="contact_name" type="text" maxlength="255" value="{{ old('contact_name', $supplier->contact_name ?? '') }}"
            class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
    </div>

    <div>
        <label for="phone" class="block text-sm font-medium">Teléfono</label>
        <input id="phone" name="phone" type="tel" maxlength="40" value="{{ old('phone', $supplier->phone ?? '') }}"
            class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
    </div>

    <div>
        <label for="email" class="block text-sm font-medium">Correo electrónico</label>
        <input id="email" name="email" type="email" maxlength="255" value="{{ old('email', $supplier->email ?? '') }}"
            class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
    </div>

    <div class="sm:col-span-2">
        <label for="address" class="block text-sm font-medium">Dirección</label>
        <textarea id="address" name="address" rows="3" maxlength="5000"
            class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">{{ old('address', $supplier->address ?? '') }}</textarea>
    </div>

    @if (isset($supplier))
        <div class="sm:col-span-2">
            <input type="hidden" name="is_active" value="0">
            <label class="flex items-center gap-2 text-sm font-medium">
                <input name="is_active" type="checkbox" value="1" @checked((bool) old('is_active', $supplier->is_active))
                    class="rounded border-stone-300 text-brand-600 focus:ring-brand-500">
                Proveedor activo
            </label>
        </div>
    @endif
</div>
