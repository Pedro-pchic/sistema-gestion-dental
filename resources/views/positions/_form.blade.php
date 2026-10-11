<div class="grid gap-5">
    <div>
        <label for="department_id" class="block text-sm font-medium">Departamento</label>
        <select id="department_id" name="department_id" required
            class="mt-2 w-full rounded-lg border border-stone-300 bg-white px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
            <option value="">Selecciona un departamento</option>
            @foreach ($departments as $department)
                <option value="{{ $department->id }}" @selected((string) old('department_id', $position->department_id ?? '') === (string) $department->id)>
                    {{ $department->name }}{{ $department->is_active ? '' : ' (Inactivo)' }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="name" class="block text-sm font-medium">Nombre del puesto</label>
        <input id="name" name="name" type="text" value="{{ old('name', $position->name ?? '') }}" required
            class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
    </div>

    <div>
        <label for="description" class="block text-sm font-medium">Descripción</label>
        <textarea id="description" name="description" rows="4"
            class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">{{ old('description', $position->description ?? '') }}</textarea>
    </div>

    <div>
        <input type="hidden" name="is_active" value="0">
        <label class="flex items-center gap-2 text-sm font-medium">
            <input name="is_active" type="checkbox" value="1" @checked((bool) old('is_active', $position->is_active ?? true))
                class="rounded border-stone-300 text-brand-600 focus:ring-brand-500">
            Puesto activo
        </label>
    </div>
</div>
