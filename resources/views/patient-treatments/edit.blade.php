<div>
    <!-- Happiness is not something readymade. It comes from your own actions. - Dalai Lama -->
</div>
@extends('layouts.admin')

@section('title', 'Editar procedimiento')
@section('heading', 'Editar procedimiento')
@section('subheading', $patient->first_name.' '.$patient->last_name.' · '.$patientTreatment->treatment->name)

@section('content')
    <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-stone-200">
        <dl class="grid gap-4 sm:grid-cols-3">
            <div><dt class="text-sm text-stone-500">Fecha</dt><dd class="mt-1 font-medium">{{ $patientTreatment->performed_at->setTimezone(config('appointments.timezone'))->format('d/m/Y H:i') }}</dd></div>
            <div><dt class="text-sm text-stone-500">Precio histórico</dt><dd class="mt-1 font-medium">Q {{ number_format((float) $patientTreatment->price_applied, 2) }}</dd></div>
            <div><dt class="text-sm text-stone-500">Odontólogo</dt><dd class="mt-1 font-medium">{{ $patientTreatment->dentist->user->name }}</dd></div>
        </dl>
    </section>
    <form method="POST" action="{{ route('pacientes.tratamientos.update', [$patient, $patientTreatment]) }}" class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
        @csrf
        @method('PUT')
        @include('patient-treatments._form')
        <div class="mt-6 flex justify-end gap-3 border-t border-stone-200 pt-5">
            <a href="{{ route('pacientes.historial-clinico', $patient) }}" class="ui-button ui-button-secondary">Cancelar</a>
            <button type="submit" class="ui-button ui-button-primary">Guardar cambios</button>
        </div>
    </form>
@endsection
