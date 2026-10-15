@extends('layouts.admin')
@section('title', 'Solicitud de cita')
@section('heading', 'Solicitud de cita')
@section('actions')<a href="{{ route('solicitudes-cita.index') }}" class="ui-button ui-button-secondary">Volver</a>@endsection
@section('content')
<div class="grid gap-6 lg:grid-cols-2">
    <section class="ui-card space-y-3 p-6"><h2 class="text-lg font-bold">Datos recibidos</h2><p><strong>Solicitante:</strong> {{ $appointmentRequest->name }}</p><p><strong>Teléfono:</strong> {{ $appointmentRequest->phone }}</p><p><strong>Correo:</strong> {{ $appointmentRequest->email }}</p><p><strong>Servicio:</strong> {{ $appointmentRequest->treatment->name }}</p><p><strong>Odontólogo:</strong> {{ $appointmentRequest->dentist->user->name }}</p><p><strong>Horario:</strong> {{ $appointmentRequest->starts_at->setTimezone(config('appointments.timezone'))->format('d/m/Y H:i') }}</p><p><strong>Recibida:</strong> {{ $appointmentRequest->created_at->setTimezone(config('appointments.timezone'))->format('d/m/Y H:i') }}</p><p><strong>Estado:</strong> {{ ['pending' => 'Pendiente', 'confirmed' => 'Confirmada', 'rejected' => 'Rechazada'][$appointmentRequest->status] ?? $appointmentRequest->status }}</p>
        @if ($appointmentRequest->status === 'pending')<p class="rounded-lg p-3 text-sm {{ $isSlotAvailable ? 'bg-emerald-50 text-emerald-900' : 'bg-amber-50 text-amber-900' }}">{{ $isSlotAvailable ? 'El horario sigue libre. Se comprobará de nuevo al confirmar.' : 'El horario está ocupado por otra cita. Rechaza la solicitud o acuerda otra fecha antes de confirmar.' }}</p>@endif
        @if ($appointmentRequest->appointment)<a class="font-semibold text-brand-700" href="{{ route('citas.show', $appointmentRequest->appointment) }}">Ver cita registrada</a>@endif
    </section>
    <div class="space-y-6">
        @if ($appointmentRequest->status === 'pending')
            <form method="POST" action="{{ route('solicitudes-cita.confirm', $appointmentRequest) }}" class="ui-card space-y-4 p-6">@csrf<h2 class="text-lg font-bold">Confirmar cita</h2><p class="text-sm text-slate-600">Revisa la disponibilidad y vincula al paciente existente cuando corresponda. Si no hay coincidencias, se creará un paciente.</p>
                <label for="patient_id" class="block text-sm font-semibold">Paciente existente</label><select id="patient_id" name="patient_id" class="w-full"><option value="">{{ $matches->isEmpty() ? 'Crear paciente nuevo' : 'Selecciona un paciente coincidente' }}</option>@foreach ($matches as $patient)<option value="{{ $patient->id }}">{{ $patient->first_name }} {{ $patient->last_name }} · {{ $patient->record_number }}</option>@endforeach</select>
                <button class="ui-button ui-button-primary">Confirmar y crear cita</button>
            </form>
            <form method="POST" action="{{ route('solicitudes-cita.reject', $appointmentRequest) }}" class="ui-card space-y-4 p-6">@csrf<h2 class="text-lg font-bold">Rechazar solicitud</h2><label for="review_note" class="block text-sm font-semibold">Motivo</label><textarea id="review_note" name="review_note" rows="3" maxlength="1000" required class="w-full">{{ old('review_note') }}</textarea><button class="ui-button ui-button-secondary">Rechazar</button></form>
        @endif
        <section class="ui-card p-6"><h2 class="text-lg font-bold">Historial</h2><ol class="mt-4 space-y-3 text-sm">@foreach ($appointmentRequest->events as $event)<li class="border-l-2 border-brand-300 pl-4"><strong>{{ ['received' => 'Recibida', 'confirmed' => 'Confirmada', 'rejected' => 'Rechazada'][$event->event_type] ?? $event->event_type }}</strong> · {{ $event->created_at->setTimezone(config('appointments.timezone'))->format('d/m/Y H:i') }} @if ($event->user) · {{ $event->user->name }} @endif @if ($event->note)<p class="mt-1">{{ $event->note }}</p>@endif</li>@endforeach</ol></section>
    </div>
</div>
@endsection
