<div class="grid gap-5 sm:grid-cols-2">
    @isset($treatments)
        <div>
            <label for="treatment_id" class="block text-sm font-medium">Tratamiento del catálogo</label>
            <select id="treatment_id" name="treatment_id" required class="mt-2 w-full rounded-lg border border-stone-300 bg-white px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
                <option value="">Selecciona un tratamiento</option>
                @foreach ($treatments as $treatment)
                    <option value="{{ $treatment->id }}" @selected((string) old('treatment_id') === (string) $treatment->id)>{{ $treatment->name }} — Q {{ number_format((float) $treatment->price, 2) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="appointment_id" class="block text-sm font-medium">Cita relacionada</label>
            <select id="appointment_id" name="appointment_id" @required(! config('dental.allow_treatment_without_appointment')) class="mt-2 w-full rounded-lg border border-stone-300 bg-white px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
                <option value="">{{ config('dental.allow_treatment_without_appointment') ? 'Sin cita' : 'Selecciona una cita' }}</option>
                @foreach ($appointments as $appointment)
                    <option value="{{ $appointment->id }}" @selected((string) old('appointment_id') === (string) $appointment->id)>
                        {{ $appointment->starts_at->setTimezone(config('appointments.timezone'))->format('d/m/Y H:i') }} — {{ $appointment->planned_treatment }}
                    </option>
                @endforeach
            </select>
            @if ($appointments->isEmpty() && ! config('dental.allow_treatment_without_appointment'))
                <p class="mt-2 text-xs text-amber-800">No hay citas completadas o en atención para este paciente y odontólogo.</p>
            @endif
        </div>
        <div>
            <label for="treatment_plan_item_id" class="block text-sm font-medium">Partida del plan (opcional)</label>
            <select id="treatment_plan_item_id" name="treatment_plan_item_id" class="mt-2 w-full rounded-lg border border-stone-300 bg-white px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
                <option value="">Sin partida de plan</option>
                @foreach ($planItems as $planItem)
                    <option value="{{ $planItem->id }}" data-treatment-id="{{ $planItem->treatment_id }}" @selected((string) old('treatment_plan_item_id') === (string) $planItem->id)>{{ $planItem->plan->title }} — {{ $planItem->treatment->name }} ({{ $planItem->patient_treatments_count }}/{{ $planItem->quantity }})</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="performed_at" class="block text-sm font-medium">Fecha y hora</label>
            <input id="performed_at" name="performed_at" type="datetime-local" value="{{ old('performed_at', now(config('appointments.timezone'))->format('Y-m-d\\TH:i')) }}" required class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
        </div>
    @endisset

    <div class="sm:col-span-2">
        <label for="procedure_description" class="block text-sm font-medium">Descripción del procedimiento</label>
        <textarea id="procedure_description" name="procedure_description" rows="4" maxlength="10000" required class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">{{ old('procedure_description', $patientTreatment->procedure_description ?? '') }}</textarea>
    </div>
    <div class="sm:col-span-2">
        <label for="clinical_notes" class="block text-sm font-medium">Observaciones clínicas</label>
        <textarea id="clinical_notes" name="clinical_notes" rows="5" maxlength="10000" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">{{ old('clinical_notes', $patientTreatment->clinical_notes ?? '') }}</textarea>
        <p class="mt-1 text-xs text-stone-500">Solo odontólogos asignados y personal con autorización clínica pueden consultar estas notas.</p>
    </div>
</div>
