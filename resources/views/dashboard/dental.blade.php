@extends('layouts.admin')

@php
    $dashboardUser = auth()->user();
    $isAdministrator = $dashboardUser->hasRole(\App\Models\Role::ADMINISTRATOR);
    $isReceptionist = $dashboardUser->hasRole(\App\Models\Role::RECEPTIONIST);
    $isDentist = $dashboardUser->hasRole(\App\Models\Role::DENTIST);
    $timezone = config('appointments.timezone');
@endphp

@section('title', 'Dashboard dental')
@section('heading', 'Dashboard')
@section('subheading', 'Agenda y actividad del consultorio')

@section('content')
    <form method="GET" action="{{ route('dashboard') }}" class="ui-card grid gap-4 p-5 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
        <label class="grid gap-2 text-sm font-semibold text-stone-700">
            Fecha desde
            <input type="date" name="from" value="{{ $from }}" required class="min-h-11 rounded-lg border border-stone-300 bg-white px-3 text-stone-900 shadow-sm focus:border-brand-600 focus:outline-2 focus:outline-brand-600">
        </label>
        <label class="grid gap-2 text-sm font-semibold text-stone-700">
            Fecha hasta
            <input type="date" name="to" value="{{ $to }}" required class="min-h-11 rounded-lg border border-stone-300 bg-white px-3 text-stone-900 shadow-sm focus:border-brand-600 focus:outline-2 focus:outline-brand-600">
        </label>
        <button type="submit" class="ui-button ui-button-primary">Filtrar período</button>
    </form>

    @php
        $cards = [
            ['label' => $isDentist ? 'Pacientes asignados' : 'Pacientes activos', 'value' => $dashboardMetrics['active_patients'], 'context' => $isDentist ? 'Fichas autorizadas' : 'En el consultorio', 'icon' => '01'],
            ['label' => 'Citas del día', 'value' => $dashboardMetrics['appointments_today'], 'context' => 'Hora local del consultorio', 'icon' => '02'],
        ];

        if ($isAdministrator) {
            $cards[] = ['label' => 'Tratamientos realizados', 'value' => $dashboardMetrics['treatments_completed'], 'context' => 'En el período filtrado', 'icon' => '03'];
            $cards[] = ['label' => 'Ingresos registrados', 'value' => 'Q '.number_format((float) $dashboardMetrics['registered_income'], 2), 'context' => 'Pagos odontológicos del período', 'icon' => '04'];
        } elseif ($isDentist) {
            $cards[] = ['label' => 'Tratamientos realizados', 'value' => $dashboardMetrics['treatments_completed'], 'context' => 'En el período filtrado', 'icon' => '03'];
            $cards[] = ['label' => 'Citas confirmadas', 'value' => $dashboardMetrics['confirmed_appointments'], 'context' => 'En el período filtrado', 'icon' => '04'];
        } else {
            $cards[] = ['label' => 'Citas por confirmar', 'value' => $dashboardMetrics['pending_appointments'], 'context' => 'Próximas 24 horas', 'icon' => '03'];
            $cards[] = ['label' => 'Cobros pendientes', 'value' => $dashboardMetrics['pending_invoice_count'], 'context' => 'Facturas con saldo', 'icon' => '04'];
        }
    @endphp

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Indicadores principales del consultorio">
        @foreach ($cards as $metric)
            <article class="ui-card relative overflow-hidden p-5 sm:p-6">
                <span class="absolute inset-x-0 top-0 h-1 bg-brand-500" aria-hidden="true"></span>
                <div class="flex items-start justify-between gap-3">
                    <p class="text-sm font-semibold text-slate-600">{{ $metric['label'] }}</p>
                    <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-brand-50 text-xs font-bold text-brand-700" aria-hidden="true">{{ $metric['icon'] }}</span>
                </div>
                <p class="mt-4 break-words text-3xl font-bold tracking-tight text-coffee tabular-nums">{{ $metric['value'] }}</p>
                <p class="mt-2 text-xs text-slate-500">{{ $metric['context'] }}</p>
            </article>
        @endforeach
    </section>

    @if ($dashboardMetrics['pending_appointments'] > 0)
        <aside class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950" role="status">
            <p class="font-semibold">Hay {{ $dashboardMetrics['pending_appointments'] }} citas sin confirmar dentro de las próximas 24 horas.</p>
            <a href="{{ route('citas.index') }}" class="mt-2 inline-flex font-semibold underline underline-offset-2">Revisar agenda</a>
        </aside>
    @endif

    <section class="grid gap-6 lg:grid-cols-2" aria-label="Resumen del consultorio">
        <article class="ui-card p-5 sm:p-6">
            <h2 class="text-lg font-bold text-coffee">Resumen de estados</h2>
            <p class="mt-1 text-sm text-slate-600">Citas del período seleccionado.</p>
            <dl class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3">
                <div class="rounded-xl bg-brand-50 p-4"><dt class="text-xs font-semibold text-brand-800">Confirmadas</dt><dd class="mt-2 text-2xl font-bold tabular-nums text-coffee">{{ $dashboardMetrics['confirmed_appointments'] }}</dd></div>
                <div class="rounded-xl bg-brand-50 p-4"><dt class="text-xs font-semibold text-brand-800">Completadas</dt><dd class="mt-2 text-2xl font-bold tabular-nums text-coffee">{{ $dashboardMetrics['completed_appointments'] }}</dd></div>
                @if ($isAdministrator)
                    <div class="rounded-xl bg-brand-50 p-4"><dt class="text-xs font-semibold text-brand-800">Canceladas</dt><dd class="mt-2 text-2xl font-bold tabular-nums text-coffee">{{ $dashboardMetrics['cancelled_appointments'] }}</dd></div>
                    <div class="rounded-xl bg-brand-50 p-4"><dt class="text-xs font-semibold text-brand-800">Odontólogos activos</dt><dd class="mt-2 text-2xl font-bold tabular-nums text-coffee">{{ $dashboardMetrics['active_dentists'] }}</dd></div>
                    <div class="rounded-xl bg-brand-50 p-4"><dt class="text-xs font-semibold text-brand-800">Facturas emitidas</dt><dd class="mt-2 text-2xl font-bold tabular-nums text-coffee">{{ $dashboardMetrics['issued_invoice_count'] }}</dd></div>
                @endif
                @if ($isAdministrator || $isReceptionist)
                    <div class="rounded-xl bg-brand-50 p-4"><dt class="text-xs font-semibold text-brand-800">Saldos pendientes</dt><dd class="mt-2 break-words text-xl font-bold tabular-nums text-coffee">Q {{ number_format((float) $dashboardMetrics['pending_balance'], 2) }}</dd></div>
                @endif
            </dl>
        </article>

        <article class="ui-card overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                <h2 class="text-lg font-bold text-coffee">Actividad reciente</h2>
                <p class="mt-1 text-sm text-slate-600">Movimientos de la agenda, sin notas clínicas.</p>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($dashboardMetrics['recent_activity'] as $activity)
                    @php
                        $activityLabel = match ($activity->event_type) {
                            'created' => 'Cita programada',
                            'rescheduled' => 'Cita reprogramada',
                            'confirmed' => 'Cita confirmada',
                            'started' => 'Atención iniciada',
                            'completed' => 'Atención completada',
                            'cancelled' => 'Cita cancelada',
                            'no_show' => 'Inasistencia registrada',
                            default => 'Cita actualizada',
                        };
                    @endphp
                    <div class="flex items-start justify-between gap-3 px-5 py-3 sm:px-6">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-coffee">{{ $activityLabel }}</p>
                            <p class="truncate text-xs text-slate-600">{{ $activity->appointment?->patient?->first_name }} {{ $activity->appointment?->patient?->last_name }}</p>
                        </div>
                        <time datetime="{{ $activity->created_at->toIso8601String() }}" class="shrink-0 text-xs text-slate-500">{{ $activity->created_at->timezone($timezone)->format('d/m H:i') }}</time>
                    </div>
                @empty
                    <p class="px-5 py-8 text-sm text-slate-500 sm:px-6">Todavía no hay actividad de citas.</p>
                @endforelse
            </div>
        </article>
    </section>

    <section class="grid gap-6 xl:grid-cols-3">
        <article class="ui-card overflow-hidden xl:col-span-2">
            <div class="flex flex-wrap items-start justify-between gap-3 border-b border-stone-200 px-5 py-4">
                <div>
                    <h2 class="text-lg font-bold text-stone-950">Próximas citas</h2>
                    <p class="mt-1 text-sm text-stone-600">Agenda de los siguientes siete días.</p>
                </div>
                <a href="{{ route('citas.index') }}" class="ui-button ui-button-secondary">Abrir agenda</a>
            </div>
            <div class="divide-y divide-stone-100">
                @forelse ($dashboardMetrics['upcoming_appointments'] as $appointment)
                    <a href="{{ route('citas.show', $appointment) }}" class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 transition hover:bg-stone-50 focus:bg-stone-50">
                        <span>
                            <span class="block font-semibold text-stone-900">{{ $appointment->patient?->first_name }} {{ $appointment->patient?->last_name }}</span>
                            <span class="mt-1 block text-sm text-stone-600">{{ $appointment->starts_at->timezone($timezone)->locale('es')->translatedFormat('D d/m/Y, H:i') }}</span>
                        </span>
                        <span class="rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $appointment->status->colorClasses() }}">{{ $appointment->status->label() }}</span>
                    </a>
                @empty
                    <p class="px-5 py-8 text-center text-sm text-stone-500">No hay citas próximas para este perfil.</p>
                @endforelse
            </div>
        </article>

        <article class="ui-card p-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-stone-950">Recordatorios</h2>
                    <p class="mt-1 text-sm text-stone-600">Canales y cola de entrega.</p>
                </div>
                <span class="size-2 rounded-full {{ $dashboardMetrics['notification_status']['email'] === 'Activo' ? 'bg-emerald-500 ring-4 ring-emerald-100' : 'bg-amber-400 ring-4 ring-amber-100' }}" aria-hidden="true"></span>
            </div>
            <dl class="mt-4 divide-y divide-stone-100">
                <div class="flex justify-between gap-4 py-3 text-sm"><dt class="text-stone-600">Correo</dt><dd class="text-right font-semibold">{{ $dashboardMetrics['notification_status']['email'] }}</dd></div>
                <div class="flex justify-between gap-4 py-3 text-sm"><dt class="text-stone-600">SMS</dt><dd class="text-right font-semibold">{{ $dashboardMetrics['notification_status']['sms'] }}</dd></div>
                <div class="flex justify-between gap-4 py-3 text-sm"><dt class="text-stone-600">Procesamiento</dt><dd class="text-right font-semibold">{{ $dashboardMetrics['notification_status']['queue'] }}</dd></div>
                @if (array_key_exists('pending', $dashboardMetrics['notification_status']))
                    <div class="flex justify-between gap-4 py-3 text-sm"><dt class="text-stone-600">En espera</dt><dd class="font-semibold tabular-nums">{{ $dashboardMetrics['notification_status']['pending'] }}</dd></div>
                    <div class="flex justify-between gap-4 py-3 text-sm"><dt class="text-stone-600">Fallidos, 7 días</dt><dd class="font-semibold tabular-nums">{{ $dashboardMetrics['notification_status']['failed_last_7_days'] }}</dd></div>
                @endif
            </dl>
            @if ($dashboardMetrics['notification_status']['email'] === 'Solo registro local')
                <p class="mt-3 rounded-lg bg-amber-50 p-3 text-xs text-amber-900">El correo está en modo de registro local; no se entrega a pacientes.</p>
            @endif
        </article>
    </section>

    @if (isset($dashboardMetrics['pending_invoices']) && $dashboardMetrics['pending_invoices']->isNotEmpty())
        <section class="ui-card overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-stone-200 px-5 py-4">
                <div>
                    <h2 class="text-lg font-bold text-stone-950">Saldos que requieren seguimiento</h2>
                    <p class="mt-1 text-sm text-stone-600">Facturas odontológicas con saldo pendiente.</p>
                </div>
                <a href="{{ route('facturacion.pagos.index') }}" class="ui-button ui-button-secondary">Ver cobros</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-stone-200 text-left text-sm">
                    <thead class="bg-stone-50 text-xs uppercase tracking-wide text-stone-500">
                        <tr><th class="px-5 py-3">Paciente</th><th class="px-5 py-3">Factura</th><th class="px-5 py-3 text-right">Saldo</th></tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        @foreach ($dashboardMetrics['pending_invoices'] as $invoice)
                            <tr>
                                <td class="px-5 py-3 font-medium">{{ $invoice->patient?->first_name }} {{ $invoice->patient?->last_name }}</td>
                                <td class="px-5 py-3"><a class="font-semibold text-brand-700 underline underline-offset-2" href="{{ route('facturacion.facturas.show', $invoice) }}">{{ $invoice->number }}</a></td>
                                <td class="px-5 py-3 text-right font-semibold tabular-nums">Q {{ number_format((float) $invoice->balance_due, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    <nav class="ui-card flex flex-wrap gap-3 p-5" aria-label="Accesos rápidos">
        <a href="{{ route('pacientes.index') }}" class="ui-button ui-button-secondary">Pacientes</a>
        <a href="{{ route('citas.index') }}" class="ui-button ui-button-secondary">Citas</a>
        @if ($isAdministrator || $isReceptionist)
            <a href="{{ route('facturacion.facturas.index') }}" class="ui-button ui-button-secondary">Facturación</a>
            <a href="{{ route('facturacion.pagos.index') }}" class="ui-button ui-button-secondary">Cobros</a>
        @endif
        @if ($isAdministrator)
            <a href="{{ route('facturacion.reportes.index') }}" class="ui-button ui-button-secondary">Reportes financieros</a>
        @endif
    </nav>
@endsection
