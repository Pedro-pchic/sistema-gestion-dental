<div class="grid gap-5 sm:grid-cols-2">
    <div>
        <label for="label" class="block text-sm font-medium">Etiqueta</label>
        <input id="label" name="label" type="text" maxlength="100" value="{{ old('label', $address->label ?? '') }}" placeholder="Casa, trabajo, sucursal"
            class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
    </div>

    <div>
        <label for="city" class="block text-sm font-medium">Municipio o ciudad</label>
        <input id="city" name="city" type="text" maxlength="255" value="{{ old('city', $address->city ?? '') }}"
            class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
    </div>

    <div>
        <label for="department" class="block text-sm font-medium">Departamento</label>
        <input id="department" name="department" type="text" maxlength="255" value="{{ old('department', $address->department ?? '') }}"
            class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
    </div>

    <div class="sm:col-span-2">
        <label for="address" class="block text-sm font-medium">Dirección completa</label>
        <textarea id="address" name="address" rows="3" maxlength="5000" required
            class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">{{ old('address', $address->address ?? '') }}</textarea>
    </div>

    @if (isset($address))
        <div class="sm:col-span-2">
            <input type="hidden" name="is_default" value="0">
            <label class="flex items-center gap-2 text-sm font-medium">
                <input name="is_default" type="checkbox" value="1" @checked((bool) old('is_default', $address->is_default))
                    class="rounded border-stone-300 text-brand-600 focus:ring-brand-500">
                Dirección predeterminada
            </label>
        </div>
    @else
        <div class="sm:col-span-2">
            <input type="hidden" name="is_default" value="0">
            <label class="flex items-center gap-2 text-sm font-medium">
                <input name="is_default" type="checkbox" value="1" @checked((bool) old('is_default', false))
                    class="rounded border-stone-300 text-brand-600 focus:ring-brand-500">
                Dirección predeterminada
            </label>
        </div>
    @endif
</div>
