<div class="grid gap-5 sm:grid-cols-2">
    <div>
        <label for="nombres" class="block text-sm font-medium">Nombres</label>
        <input id="nombres" name="nombres" type="text" value="{{ old('nombres', $employee->nombres ?? '') }}" required
            class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
    </div>

    <div>
        <label for="apellidos" class="block text-sm font-medium">Apellidos</label>
        <input id="apellidos" name="apellidos" type="text" value="{{ old('apellidos', $employee->apellidos ?? '') }}" required
            class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
    </div>

    <div>
        <label for="user_id" class="block text-sm font-medium">Usuario asociado</label>
        <select id="user_id" name="user_id"
            class="mt-2 w-full rounded-lg border border-stone-300 bg-white px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
            <option value="">Sin usuario asociado</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}" @selected((string) old('user_id', $employee->user_id ?? '') === (string) $user->id)>
                    {{ $user->name }} — {{ $user->email }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="position_id" class="block text-sm font-medium">Puesto organizacional</label>
        <select id="position_id" name="position_id"
            class="mt-2 w-full rounded-lg border border-stone-300 bg-white px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
            <option value="">Sin puesto asignado</option>
            @foreach ($positions as $position)
                <option value="{{ $position->id }}" @selected((string) old('position_id', $employee->position_id ?? '') === (string) $position->id)>
                    {{ $position->name }} — {{ $position->department->name }}{{ $position->is_active ? '' : ' (Inactivo)' }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="puesto" class="block text-sm font-medium">Puesto registrado (descripción anterior)</label>
        <input id="puesto" name="puesto" type="text" value="{{ old('puesto', $employee->puesto ?? '') }}"
            class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
    </div>

    <div>
        <label for="telefono" class="block text-sm font-medium">Teléfono</label>
        <input id="telefono" name="telefono" type="tel" value="{{ old('telefono', $employee->telefono ?? '') }}"
            class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
    </div>

    <div>
        <label for="fecha_contratacion" class="block text-sm font-medium">Fecha de contratación</label>
        <input id="fecha_contratacion" name="fecha_contratacion" type="date" value="{{ old('fecha_contratacion', isset($employee) ? $employee->fecha_contratacion?->format('Y-m-d') : '') }}"
            class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
    </div>

    <div class="sm:col-span-2">
        <label for="direccion" class="block text-sm font-medium">Dirección</label>
        <textarea id="direccion" name="direccion" rows="3"
            class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">{{ old('direccion', $employee->direccion ?? '') }}</textarea>
    </div>

    <div class="sm:col-span-2">
        <input type="hidden" name="is_active" value="0">
        <label class="flex items-center gap-2 text-sm font-medium">
            <input name="is_active" type="checkbox" value="1" @checked((bool) old('is_active', $employee->is_active ?? true))
                class="rounded border-stone-300 text-brand-600 focus:ring-brand-500">
            Empleado activo
        </label>
    </div>
</div>
