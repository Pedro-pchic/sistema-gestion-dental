@extends('layouts.admin')

@section('title', $treatmentPlan->title)
@section('heading', $treatmentPlan->title)
@section('subheading', $patient->first_name.' '.$patient->last_name.' · Plan odontológico')

@section('actions')
    <a href="{{ route('pacientes.planes.index', $patient) }}" class="ui-button ui-button-secondary">Todos los planes</a>
    <a href="{{ route('pacientes.historial-clinico', $patient) }}" class="ui-button ui-button-secondary">Historial clínico</a>
@endsection

@section('content')
    @php($estimatedTotal = $treatmentPlan->items->sum(fn ($item) => (float) $item->estimated_unit_price * $item->quantity))
    <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-stone-200">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <span class="ui-badge {{ $treatmentPlan->status->colorClasses() }}">{{ $treatmentPlan->status->label() }}</span>
                <p class="mt-3 max-w-3xl whitespace-pre-line text-sm text-stone-700">{{ $treatmentPlan->description ?: 'Sin descripción adicional.' }}</p>
                <p class="mt-3 text-sm text-stone-500">Odontólogo: {{ $treatmentPlan->dentist->user->name }} · Creado por {{ $treatmentPlan->creator->name }} · {{ $treatmentPlan->created_at->format('d/m/Y') }}</p>
            </div>
            <div class="rounded-lg bg-brand-50 px-4 py-3 text-right"><p class="text-xs font-semibold uppercase tracking-wide text-brand-800">Total estimado</p><p class="mt-1 text-xl font-bold tabular-nums text-brand-950">Q {{ number_format($estimatedTotal, 2) }}</p></div>
        </div>
        @can('update', $treatmentPlan)
            @if (in_array($treatmentPlan->status, [\App\Enums\TreatmentPlanStatus::Pending, \App\Enums\TreatmentPlanStatus::InProgress], true))
                <form method="POST" action="{{ route('pacientes.planes.update', [$patient, $treatmentPlan]) }}" class="mt-5 flex flex-wrap items-end gap-3 border-t border-stone-200 pt-4">
                    @csrf
                    @method('PATCH')
                    <div><label for="status" class="block text-sm font-medium">Cambiar estado del plan</label><select id="status" name="status" required class="mt-2 rounded-lg border border-stone-300 bg-white px-3 py-2">
                        <option value="">Selecciona estado</option>
                        @if ($treatmentPlan->status === \App\Enums\TreatmentPlanStatus::Pending)<option value="in_progress">En proceso</option>@endif
                        @if ($treatmentPlan->status === \App\Enums\TreatmentPlanStatus::InProgress && $treatmentPlan->items->every(fn ($item) => $item->status === \App\Enums\TreatmentPlanStatus::Completed))<option value="completed">Completado</option>@endif
                        <option value="cancelled">Cancelado</option>
                    </select></div>
                    <button type="submit" class="ui-button ui-button-secondary">Actualizar estado</button>
                </form>
            @endif
        @endcan
    </section>

    <section class="rounded-xl bg-white shadow-sm ring-1 ring-stone-200">
        <div class="border-b border-stone-200 px-5 py-4"><h2 class="font-semibold">Tratamientos previstos</h2><p class="mt-1 text-sm text-stone-500">Un tratamiento realizado queda registrado por separado en el historial clínico.</p></div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200 text-sm">
                <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-600"><tr><th class="px-5 py-3">Tratamiento</th><th class="px-5 py-3">Cantidad</th><th class="px-5 py-3">Precio estimado</th><th class="px-5 py-3">Estado</th><th class="px-5 py-3">Avance</th></tr></thead>
                <tbody class="divide-y divide-stone-100">
                    @foreach ($treatmentPlan->items as $item)
                        <tr>
                            <td class="px-5 py-4 font-medium">{{ $item->treatment->name }}</td>
                            <td class="px-5 py-4">{{ $item->quantity }}</td>
                            <td class="px-5 py-4 tabular-nums">Q {{ number_format((float) $item->estimated_unit_price, 2) }} por unidad</td>
                            <td class="px-5 py-4"><span class="ui-badge {{ $item->status->colorClasses() }}">{{ $item->status->label() }}</span></td>
                            <td class="px-5 py-4">
                                @if ($item->patientTreatments->isNotEmpty())
                                    <a href="{{ route('pacientes.historial-clinico', $patient) }}" class="font-semibold text-brand-800 hover:underline">{{ $item->patientTreatments->count() }} de {{ $item->quantity }} registrados</a>
                                    <ul class="mt-1 space-y-1 text-xs text-stone-500">
                                        @foreach ($item->patientTreatments as $procedure)
                                            <li>{{ $procedure->performed_at->setTimezone(config('appointments.timezone'))->format('d/m/Y H:i') }}</li>
                                        @endforeach
                                    </ul>
                                @elseif ($item->status !== \App\Enums\TreatmentPlanStatus::Cancelled && $treatmentPlan->status !== \App\Enums\TreatmentPlanStatus::Cancelled)
                                    <span class="text-stone-500">Pendiente de registro</span>
                                @else
                                    <span class="text-stone-500">Sin procedimiento asociado</span>
                                @endif
                                @can('update', $treatmentPlan)
                                    @if (in_array($item->status, [\App\Enums\TreatmentPlanStatus::Pending, \App\Enums\TreatmentPlanStatus::InProgress], true) && $treatmentPlan->status !== \App\Enums\TreatmentPlanStatus::Cancelled)
                                        <form method="POST" action="{{ route('pacientes.planes.items.update', [$patient, $treatmentPlan, $item]) }}" class="mt-2 flex flex-wrap gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <select name="status" required aria-label="Estado de {{ $item->treatment->name }}" class="rounded-md border border-stone-300 bg-white px-2 py-1.5 text-xs">
                                                <option value="">Actualizar estado</option>
                                                @if ($item->status === \App\Enums\TreatmentPlanStatus::Pending)<option value="in_progress">En proceso</option>@endif
                                                <option value="cancelled">Cancelar partida</option>
                                            </select>
                                            <button type="submit" class="text-xs font-semibold text-brand-800 hover:underline">Guardar</button>
                                        </form>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endsection
