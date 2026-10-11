@php
    $patient = $patient ?? null;
    $selectedDentistIds = collect(old('dentist_ids', $assignedDentistIds ?? []))
        ->map(fn ($id) => (int) $id)
        ->all();
@endphp

<div class="grid gap-5 sm:grid-cols-2">
    <div>
        <label for="first_name" class="block text-sm font-medium">Nombres</label>
        <input id="first_name" name="first_name" type="text" maxlength="120" value="{{ old('first_name', $patient?->first_name ?? '') }}" required
            class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
        @error('first_name')
            <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="last_name" class="block text-sm font-medium">Apellidos</label>
        <input id="last_name" name="last_name" type="text" maxlength="120" value="{{ old('last_name', $patient?->last_name ?? '') }}" required
            class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
        @error('last_name')
            <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="date_of_birth" class="block text-sm font-medium">Fecha de nacimiento</label>
        <input id="date_of_birth" name="date_of_birth" type="date" max="{{ today()->toDateString() }}"
            value="{{ old('date_of_birth', $patient?->date_of_birth?->format('Y-m-d') ?? '') }}"
            class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
        @error('date_of_birth')
            <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="phone" class="block text-sm font-medium">Teléfono</label>
        <input id="phone" name="phone" type="tel" maxlength="40" value="{{ old('phone', $patient?->phone ?? '') }}"
            class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
        @error('phone')
            <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
        @enderror
    </div>

    <div class="sm:col-span-2">
        <label for="email" class="block text-sm font-medium">Correo electrónico</label>
        <input id="email" name="email" type="email" maxlength="255" value="{{ old('email', $patient?->email ?? '') }}"
            class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
        @error('email')
            <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
        @enderror
    </div>

    <div class="sm:col-span-2">
        <label for="address" class="block text-sm font-medium">Dirección</label>
        <textarea id="address" name="address" rows="3" maxlength="2000"
            class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">{{ old('address', $patient?->address ?? '') }}</textarea>
        @error('address')
            <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
        @enderror
    </div>

    @if ($canAssignDentists)
        <fieldset class="sm:col-span-2">
            @if ($patient)
                <input type="hidden" name="dentist_ids_present" value="1">
            @endif
            <legend class="text-sm font-medium">Odontólogos autorizados</legend>
            <p class="mt-1 text-xs text-stone-500">Solo los odontólogos seleccionados podrán consultar esta ficha.</p>
            <div class="mt-3 grid gap-2 sm:grid-cols-2">
                @forelse ($dentists as $dentist)
                    <label class="flex items-start gap-3 rounded-lg border border-stone-200 p-3 text-sm">
                        <input name="dentist_ids[]" type="checkbox" value="{{ $dentist->id }}"
                            @checked(in_array($dentist->id, $selectedDentistIds, true))
                            class="mt-0.5 rounded border-stone-300 text-brand-600 focus:ring-brand-500">
                        <span>
                            <span class="block font-medium">{{ $dentist->user->name }}</span>
                            <span class="block text-xs text-stone-500">{{ $dentist->specialty ?: 'Sin especialidad registrada' }}</span>
                        </span>
                    </label>
                @empty
                    <p class="text-sm text-stone-500">Primero crea un usuario con el rol Dentista.</p>
                @endforelse
            </div>
            @error('dentist_ids')
                <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
            @enderror
            @error('dentist_ids.*')
                <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
            @enderror
        </fieldset>
    @endif

    @if ($patient)
        <div class="sm:col-span-2">
            <input type="hidden" name="is_active" value="0">
            <label class="flex items-center gap-2 text-sm font-medium">
                <input name="is_active" type="checkbox" value="1" @checked((bool) old('is_active', $patient->is_active))
                    class="rounded border-stone-300 text-brand-600 focus:ring-brand-500">
                Paciente activo
            </label>
            @error('is_active')
                <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
            @enderror
        </div>
    @endif
</div>
