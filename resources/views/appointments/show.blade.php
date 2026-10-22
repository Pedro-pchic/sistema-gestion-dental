@extends('layouts.admin')

@section('title', 'Detalle de cita')
@section('heading', 'Detalle de cita')
@section('subheading', 'Horario en '.$timezone)

@section('content')
    @php
        $eventLabels = [
            'created' => 'Cita programada',
            'rescheduled' => 'Horario reprogramado',
            'confirmed' => 'Asistencia confirmada',
            'started' => 'Atención iniciada',
            'completed' => 'Atención completada',
            'cancelled' => 'Cita cancelada',
            'no_show' => 'Inasistencia registrada',
        ];
    @endphp

    <section class="ui-card p-5 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4 border-b border-stone-200 pb-5">
            <div>
                <h2 class="text-xl font-bold text-stone-950">{{ $appointment->patient?->first_name }} {{ $appointment->patient?->last_name ?? 'Paciente eliminado' }}</h2>
                <p class="mt-1 text-sm text-stone-600">{{ $appointment->planned_treatment }}</p>
            </div>
            <span class="inline-flex items-center rounded-full px-3 py-1.5 text-sm font-semibold ring-1 ring-inset {{ $appointment->status->colorClasses() }}">
                {{ $appointment->status->label() }}
            </span>
        </div>

        <dl class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-stone-500">Inicio</dt>
                <dd class="mt-1 font-semibold tabular-nums">{{ $appointment->starts_at->setTimezone($timezone)->format('d/m/Y H:i') }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-stone-500">Finalización</dt>
                <dd class="mt-1 font-semibold tabular-nums">{{ $appointment->ends_at->setTimezone($timezone)->format('d/m/Y H:i') }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-stone-500">Duración</dt>
                <dd class="mt-1 font-semibold tabular-nums">{{ $appointment->starts_at->diffInMinutes($appointment->ends_at) }} min</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-stone-500">Odontólogo responsable</dt>
                <dd class="mt-1">{{ $appointment->dentist?->user?->name ?? 'Odontólogo no disponible' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-stone-500">Registrada por</dt>
                <dd class="mt-1">{{ $appointment->creator?->name ?? 'Usuario no disponible' }}</dd>
            </div>
            @if ($appointment->confirmed_at)
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-stone-500">Confirmada</dt>
                    <dd class="mt-1">{{ $appointment->confirmed_at->setTimezone($timezone)->format('d/m/Y H:i') }}</dd>
                </div>
            @endif
            @if ($appointment->started_at)
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-stone-500">Atención iniciada</dt>
                    <dd class="mt-1">{{ $appointment->started_at->setTimezone($timezone)->format('d/m/Y H:i') }}</dd>
                </div>
            @endif
            @if ($appointment->completed_at)
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-stone-500">Atención completada</dt>
                    <dd class="mt-1">{{ $appointment->completed_at->setTimezone($timezone)->format('d/m/Y H:i') }}</dd>
                </div>
            @endif
        </dl>

        @if ($appointment->administrative_notes)
            <div class="mt-6 rounded-lg border border-stone-200 bg-stone-50 p-4">
                <h3 class="text-sm font-semibold text-stone-700">Observaciones administrativas</h3>
                <p class="mt-2 whitespace-pre-line text-sm text-stone-700">{{ $appointment->administrative_notes }}</p>
                <p class="mt-2 text-xs text-stone-500">Este campo no debe contener información clínica.</p>
            </div>
        @endif

        @if ($appointment->cancellation_reason)
            <div class="mt-5 rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-950">
                <p class="font-semibold">Motivo de cancelación</p>
                <p class="mt-1 whitespace-pre-line">{{ $appointment->cancellation_reason }}</p>
                <p class="mt-1 text-xs">Cancelada por {{ $appointment->canceller?->name ?? 'Usuario no disponible' }}
                    @if ($appointment->cancelled_at)
                        el {{ $appointment->cancelled_at->setTimezone($timezone)->format('d/m/Y H:i') }}
                    @endif
                </p>
            </div>
        @endif

        <div class="mt-6 flex flex-wrap gap-2 border-t border-stone-200 pt-5">
            @can('reschedule', $appointment)
                <a href="{{ route('citas.edit', $appointment) }}" class="ui-button ui-button-secondary">Reprogramar</a>
            @endcan

            @can('confirm', $appointment)
                <form method="POST" action="{{ route('citas.confirm', $appointment) }}">
                    @csrf
                    <button type="submit" class="ui-button ui-button-secondary">Confirmar asistencia</button>
                </form>
            @endcan

            @can('start', $appointment)
                <form method="POST" action="{{ route('citas.start', $appointment) }}">
                    @csrf
                    <button type="submit" class="ui-button ui-button-primary">Iniciar atención</button>
                </form>
            @endcan

            @can('complete', $appointment)
                <form method="POST" action="{{ route('citas.complete', $appointment) }}">
                    @csrf
                    <button type="submit" class="ui-button ui-button-primary">Completar atención</button>
                </form>
            @endcan

            @can('markNoShow', $appointment)
                <form method="POST" action="{{ route('citas.no-show', $appointment) }}">
                    @csrf
                    <button type="submit" class="ui-button ui-button-secondary">Marcar inasistencia</button>
                </form>
            @endcan

            @can('cancel', $appointment)
                <form method="POST" action="{{ route('citas.cancel', $appointment) }}" data-confirm="¿Cancelar esta cita?" class="grid w-full gap-2 rounded-lg border border-rose-200 bg-rose-50 p-3 sm:grid-cols-[1fr_auto] sm:items-end">
                    @csrf
                    <label for="reason" class="grid gap-1 text-sm font-semibold text-rose-950">
                        Motivo para cancelar
                        <input id="reason" name="reason" type="text" minlength="5" maxlength="1000" required value="{{ old('reason') }}"
                            @error('reason') aria-invalid="true" aria-describedby="reason-error" @enderror
                            class="min-h-10 rounded-lg border border-rose-300 bg-white px-3 text-stone-900 focus:border-rose-600 focus:outline-2 focus:outline-rose-600">
                    </label>
                    <button type="submit" class="rounded-lg bg-rose-700 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-800">Cancelar cita</button>
                    @error('reason')
                        <span id="reason-error" class="text-sm font-normal text-rose-800 sm:col-span-2">{{ $message }}</span>
                    @enderror
                </form>
            @endcan

            <a href="{{ route('citas.index') }}" class="ui-button ui-button-secondary">Volver a la agenda</a>

            @if ($appointment->patient)
                @can('view', $appointment->patient)
                    <a href="{{ route('pacientes.citas.index', $appointment->patient) }}" class="ui-button ui-button-secondary">Historial del paciente</a>
                @endcan
            @endif
        </div>
    </section>

    <section class="ui-card p-5 sm:p-6" aria-labelledby="appointment-history-heading">
        <h2 id="appointment-history-heading" class="text-lg font-bold text-stone-950">Historial de cambios</h2>
        <ol class="mt-4 divide-y divide-stone-200">
            @foreach ($appointment->events as $event)
                <li class="grid gap-1 py-4 first:pt-0 sm:grid-cols-[12rem_1fr]">
                    <time class="text-sm tabular-nums text-stone-500" datetime="{{ $event->created_at->toIso8601String() }}">
                        {{ $event->created_at->setTimezone($timezone)->format('d/m/Y H:i') }}
                    </time>
                    <div class="text-sm">
                        <p class="font-semibold text-stone-900">{{ $eventLabels[$event->event_type] ?? 'Cambio registrado' }}</p>
                        <p class="text-stone-600">Por {{ $event->user?->name ?? 'Usuario no disponible' }}</p>
                        @if ($event->event_type === 'rescheduled')
                            @foreach ($event->changes ?? [] as $field => $change)
                                @if (is_array($change) && isset($change['from'], $change['to']))
                                    <p class="mt-1 text-xs text-stone-600">
                                        {{ $field === 'starts_at' ? 'Inicio' : 'Finalización' }}:
                                        {{ \Carbon\CarbonImmutable::parse($change['from'])->setTimezone($timezone)->format('d/m/Y H:i') }}
                                        →
                                        {{ \Carbon\CarbonImmutable::parse($change['to'])->setTimezone($timezone)->format('d/m/Y H:i') }}
                                    </p>
                                @endif
                            @endforeach
                        @endif
                        @if ($event->reason)
                            <p class="mt-1 whitespace-pre-line text-stone-600">Motivo: {{ $event->reason }}</p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    </section>
@endsection
