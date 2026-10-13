<form method="POST" action="{{ route('citas.store') }}" class="ui-card grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
    @csrf

    <label for="patient_id" class="grid gap-2 text-sm font-semibold text-stone-700">
        Paciente
        <select id="patient_id" name="patient_id" required class="min-h-11 rounded-lg border border-stone-300 bg-white px-3 text-stone-900 shadow-sm focus:border-brand-600 focus:outline-2 focus:outline-brand-600">
            <option value="">Selecciona un paciente</option>
            @foreach ($patients as $patient)
                <option value="{{ $patient->id }}" @selected((string) old('patient_id') === (string) $patient->id)>
                    {{ $patient->last_name }}, {{ $patient->first_name }} · {{ $patient->record_number }}
                </option>
            @endforeach
        </select>
        @error('patient_id')
            <span class="font-normal text-red-700">{{ $message }}</span>
        @enderror
    </label>

    <label for="dentist_id" class="grid gap-2 text-sm font-semibold text-stone-700">
        Odontólogo responsable
        <select id="dentist_id" name="dentist_id" required class="min-h-11 rounded-lg border border-stone-300 bg-white px-3 text-stone-900 shadow-sm focus:border-brand-600 focus:outline-2 focus:outline-brand-600">
            <option value="">Selecciona un odontólogo</option>
            @foreach ($dentists as $dentist)
                <option value="{{ $dentist->id }}" @selected((string) old('dentist_id') === (string) $dentist->id)>
                    {{ $dentist->user->name }}{{ $dentist->specialty ? ' · '.$dentist->specialty : '' }}
                </option>
            @endforeach
        </select>
        <span class="font-normal text-stone-500">El odontólogo debe estar asignado a la ficha del paciente.</span>
        @error('dentist_id')
            <span class="font-normal text-red-700">{{ $message }}</span>
        @enderror
    </label>

    <label for="starts_at" class="grid gap-2 text-sm font-semibold text-stone-700">
        Inicio ({{ $timezone }})
        <input id="starts_at" name="starts_at" type="datetime-local" step="900" required
            value="{{ old('starts_at', $defaultStartsAt) }}"
            class="min-h-11 rounded-lg border border-stone-300 bg-white px-3 text-stone-900 shadow-sm focus:border-brand-600 focus:outline-2 focus:outline-brand-600">
        @error('starts_at')
            <span class="font-normal text-red-700">{{ $message }}</span>
        @enderror
    </label>

    <label for="ends_at" class="grid gap-2 text-sm font-semibold text-stone-700">
        Finalización ({{ $timezone }})
        <input id="ends_at" name="ends_at" type="datetime-local" step="900" required
            value="{{ old('ends_at', $defaultEndsAt) }}"
            class="min-h-11 rounded-lg border border-stone-300 bg-white px-3 text-stone-900 shadow-sm focus:border-brand-600 focus:outline-2 focus:outline-brand-600">
        @error('ends_at')
            <span class="font-normal text-red-700">{{ $message }}</span>
        @enderror
    </label>

    <label for="planned_treatment" class="grid gap-2 text-sm font-semibold text-stone-700 sm:col-span-2">
        Tratamiento previsto (descripción provisional)
        <input id="planned_treatment" name="planned_treatment" type="text" maxlength="255" required
            value="{{ old('planned_treatment') }}"
            class="min-h-11 rounded-lg border border-stone-300 bg-white px-3 text-stone-900 shadow-sm focus:border-brand-600 focus:outline-2 focus:outline-brand-600">
        @error('planned_treatment')
            <span class="font-normal text-red-700">{{ $message }}</span>
        @enderror
    </label>

    <label for="administrative_notes" class="grid gap-2 text-sm font-semibold text-stone-700 sm:col-span-2">
        Observaciones administrativas
        <textarea id="administrative_notes" name="administrative_notes" rows="3" maxlength="2000"
            aria-describedby="administrative-notes-help"
            class="rounded-lg border border-stone-300 bg-white px-3 py-2 text-stone-900 shadow-sm focus:border-brand-600 focus:outline-2 focus:outline-brand-600">{{ old('administrative_notes') }}</textarea>
        <span id="administrative-notes-help" class="font-normal text-stone-500">No registres información clínica en este campo.</span>
        @error('administrative_notes')
            <span class="font-normal text-red-700">{{ $message }}</span>
        @enderror
    </label>

    <div class="flex flex-wrap gap-3 sm:col-span-2">
        <button type="submit" class="ui-button ui-button-primary">Programar cita</button>
        <a href="{{ route('citas.index') }}" class="ui-button ui-button-secondary">Cancelar</a>
    </div>
</form>
