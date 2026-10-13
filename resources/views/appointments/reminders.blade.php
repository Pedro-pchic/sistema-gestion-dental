@extends('layouts.admin')

@section('title', 'Recordatorios de citas')
@section('heading', 'Centro de recordatorios')
@section('subheading', 'Seguimiento de comunicaciones administrativas de citas')

@section('content')
    @if (in_array(config('mail.default'), ['log', 'array'], true))
        <p class="ui-card mb-4 p-4 text-sm text-amber-900">El correo usa un transporte local de prueba. «Enviado» indica que el transporte aceptó el mensaje; no confirma entrega al paciente.</p>
    @endif
    <form method="GET" action="{{ route('recordatorios.index') }}" class="ui-card grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-5 lg:items-end" aria-label="Filtrar recordatorios">
        <label class="grid gap-1 text-sm font-medium text-stone-700">Desde
            <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="rounded-md border border-stone-300 px-3 py-2 font-normal">
        </label>
        <label class="grid gap-1 text-sm font-medium text-stone-700">Hasta
            <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="rounded-md border border-stone-300 px-3 py-2 font-normal">
        </label>
        <label class="grid gap-1 text-sm font-medium text-stone-700">Estado
            <select name="status" class="rounded-md border border-stone-300 px-3 py-2 font-normal">
                <option value="">Todos</option>
                @foreach (\App\Enums\AppointmentReminderStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </label>
        <label class="grid gap-1 text-sm font-medium text-stone-700">Canal
            <select name="channel" class="rounded-md border border-stone-300 px-3 py-2 font-normal">
                <option value="">Todos</option>
                <option value="email" @selected(($filters['channel'] ?? '') === 'email')>Correo</option>
                <option value="sms" @selected(($filters['channel'] ?? '') === 'sms')>SMS</option>
            </select>
        </label>
        <button type="submit" class="ui-button ui-button-secondary">Filtrar</button>
    </form>

    <section class="ui-card mt-6 overflow-hidden" aria-label="Recordatorios registrados">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[70rem] divide-y divide-stone-200 text-sm">
                <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-600">
                    <tr><th class="px-4 py-3">Cita</th><th class="px-4 py-3">Paciente</th><th class="px-4 py-3">Canal</th><th class="px-4 py-3">Programado</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3 text-right">Intentos</th><th class="px-4 py-3">Último error</th><th class="px-4 py-3">Enviado</th></tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($reminders as $reminder)
                        <tr>
                            <td class="px-4 py-3 font-semibold">
                                @if ($reminder->appointment)
                                    <a href="{{ route('citas.show', $reminder->appointment) }}" class="text-brand-700 underline-offset-2 hover:underline">#{{ $reminder->appointment_id }}</a>
                                @else
                                    #{{ $reminder->appointment_id }}
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $reminder->patient ? $reminder->patient->first_name.' '.$reminder->patient->last_name : 'Paciente no disponible' }}</td>
                            <td class="px-4 py-3">{{ $reminder->channel === 'email' ? 'Correo' : 'SMS' }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ $reminder->scheduled_at->timezone($timezone)->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3"><span class="ui-badge" data-status="{{ $reminder->status->value }}">{{ $reminder->status->label() }}</span></td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $reminder->attempts }}</td>
                            <td class="px-4 py-3 text-stone-600">{{ $reminder->last_error ? (str_starts_with($reminder->last_error, 'Queue') ? 'No se pudo encolar.' : (str_starts_with($reminder->last_error, 'Delivery worker') ? 'El proceso no terminó.' : 'Falló la entrega.')) : '—' }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ $reminder->sent_at?->timezone($timezone)->format('d/m/Y H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-8 text-center text-stone-600">No hay recordatorios que coincidan con los filtros.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <div class="mt-4">{{ $reminders->links() }}</div>
@endsection
