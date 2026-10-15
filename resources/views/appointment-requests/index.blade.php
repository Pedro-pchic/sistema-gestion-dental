@extends('layouts.admin')
@section('title', 'Solicitudes web')
@section('heading', 'Solicitudes de cita')
@section('subheading', 'Solicitudes recibidas desde la página pública')
@section('actions')<a href="{{ route('citas.index') }}" class="ui-button ui-button-secondary">Agenda de citas</a>@endsection
@section('content')
<p class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-950"><strong>{{ $pendingCount }} pendientes.</strong> Una solicitud pendiente no reserva el horario; recepción confirma disponibilidad al crear la cita.</p>
<form method="GET" action="{{ route('solicitudes-cita.index') }}" class="ui-card grid gap-4 p-5 sm:grid-cols-2 xl:grid-cols-4 xl:items-end">
    <label class="grid gap-2 text-sm font-semibold">Fecha solicitada<input name="date" type="date" value="{{ $filters['date'] ?? '' }}" class="w-full"></label>
    <label class="grid gap-2 text-sm font-semibold">Estado<select name="status" class="w-full"><option value="">Todos</option>@foreach (['pending' => 'Pendiente', 'confirmed' => 'Confirmada', 'rejected' => 'Rechazada'] as $value => $label)<option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></label>
    <label class="grid gap-2 text-sm font-semibold">Odontólogo<select name="dentist_id" class="w-full"><option value="">Todos</option>@foreach ($dentists as $dentist)<option value="{{ $dentist->id }}" @selected((string) ($filters['dentist_id'] ?? '') === (string) $dentist->id)>{{ $dentist->user->name }}</option>@endforeach</select></label>
    <div class="flex gap-2"><button class="ui-button ui-button-primary">Filtrar</button><a href="{{ route('solicitudes-cita.index') }}" class="ui-button ui-button-secondary">Limpiar</a></div>
</form>
<div class="ui-card overflow-x-auto p-5">
    <table class="w-full min-w-[62rem] text-left text-sm">
        <thead><tr class="border-b text-slate-500"><th class="p-3">Cliente</th><th class="p-3">Servicio</th><th class="p-3">Odontólogo</th><th class="p-3">Fecha solicitada</th><th class="p-3">Hora</th><th class="p-3">Recibida</th><th class="p-3">Estado</th><th class="p-3">Acción</th></tr></thead>
        <tbody>
            @forelse ($requests as $booking)
                <tr class="border-b border-slate-100">
                    <td class="p-3 font-semibold">{{ $booking->name }}</td><td class="p-3">{{ $booking->treatment->name }}</td><td class="p-3">{{ $booking->dentist->user->name }}</td>
                    <td class="p-3">{{ $booking->starts_at->setTimezone(config('appointments.timezone'))->format('d/m/Y') }}</td><td class="p-3">{{ $booking->starts_at->setTimezone(config('appointments.timezone'))->format('H:i') }}</td>
                    <td class="p-3">{{ $booking->created_at->setTimezone(config('appointments.timezone'))->format('d/m/Y H:i') }}</td>
                    <td class="p-3"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ ['pending' => 'bg-amber-100 text-amber-900', 'confirmed' => 'bg-emerald-100 text-emerald-900', 'rejected' => 'bg-slate-100 text-slate-700'][$booking->status] ?? '' }}">{{ ['pending' => 'Pendiente', 'confirmed' => 'Confirmada', 'rejected' => 'Rechazada'][$booking->status] ?? $booking->status }}</span></td>
                    <td class="p-3"><a class="font-semibold text-brand-700 hover:underline" href="{{ route('solicitudes-cita.show', $booking) }}">Revisar</a></td>
                </tr>
            @empty
                <tr><td colspan="8" class="p-6 text-center text-slate-500">No hay solicitudes para estos filtros.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="mt-5">{{ $requests->links() }}</div>
</div>
@endsection
