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
            ['label' => $isDentist ? 'Pacientes asignados' : 'Pacientes activos', 'value' => $dashboardMetrics['active_patients'], 'context' => $isDentist ? 'Fichas autorizadas' : 'En el consultorio'],
            ['label' => 'Citas programadas hoy', 'value' => $dashboardMetrics['appointments_today'], 'context' => 'Hora local del consultorio'],
            ['label' => 'Citas confirmadas', 'value' => $dashboardMetrics['confirmed_appointments'], 'context' => 'En el período filtrado'],
        ];

        if ($isAdministrator || $isDentist) {
            $cards[] = ['label' => 'Citas completadas', 'value' => $dashboardMetrics['completed_appointments'], 'context' => 'En el período filtrado'];
            $cards[] = ['label' => 'Tratamientos realizados', 'value' => $dashboardMetrics['treatments_completed'], 'context' => 'En el período filtrado'];
        }

        if ($isAdministrator) {
            $cards[] = ['label' => 'Citas canceladas', 'value' => $dashboardMetrics['cancelled_appointments'], 'context' => 'En el período filtrado'];
            $cards[] = ['label' => 'Odontólogos activos', 'value' => $dashboardMetrics['active_dentists'], 'context' => 'Perfiles habilitados'];
            $cards[] = ['label' => 'Facturas emitidas', 'value' => $dashboardMetrics['issued_invoice_count'], 'context' => 'En el período filtrado'];
            $cards[] = ['label' => 'Ingresos registrados', 'value' => 'Q '.number_format((float) $dashboardMetrics['registered_income'], 2), 'context' => 'Pagos odontológicos del período'];
            $cards[] = ['label' => 'Saldos pendientes', 'value' => 'Q '.number_format((float) $dashboardMetrics['pending_balance'], 2), 'context' => $dashboardMetrics['pending_invoice_count'].' facturas pendientes'];
        } elseif ($isReceptionist) {
            $cards[] = ['label' => 'Citas por confirmar', 'value' => $dashboardMetrics['pending_appointments'], 'context' => 'Próximas 24 horas'];
            $cards[] = ['label' => 'Cobros pendientes', 'value' => $dashboardMetrics['pending_invoice_count'], 'context' => 'Facturas con saldo'];
            $cards[] = ['label' => 'Saldo por cobrar', 'value' => 'Q '.number_format((float) $dashboardMetrics['pending_balance'], 2), 'context' => 'Facturas con saldo pendiente'];
        }
    @endphp

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Indicadores del consultorio">
        @foreach ($cards as $metric)
            <article class="ui-card relative overflow-hidden p-5">
                <span class="absolute inset-y-0 left-0 w-1 bg-brand-600" aria-hidden="true"></span>
                <p class="text-sm font-semibold text-stone-600">{{ $metric['label'] }}</p>
                <p class="mt-3 break-words text-3xl font-bold tracking-tight text-stone-950 tabular-nums">{{ $metric['value'] }}</p>
                <p class="mt-2 text-xs text-stone-500">{{ $metric['context'] }}</p>
            </article>
        @endforeach
    </section>

    @if ($dashboardMetrics['pending_appointments'] > 0)
        <aside class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950" role="status">
            <p class="font-semibold">Hay {{ $dashboardMetrics['pending_appointments'] }} citas sin confirmar dentro de las próximas 24 horas.</p>
            <a href="{{ route('citas.index') }}" class="mt-2 inline-flex font-semibold underline underline-offset-2">Revisar agenda</a>
        </aside>
    @endif

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
