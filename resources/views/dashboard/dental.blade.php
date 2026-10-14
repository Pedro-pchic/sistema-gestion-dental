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
    @if ($errors->any())
        <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900">{{ $errors->first() }}</div>
    @endif

    <form method="GET" action="{{ route('dashboard') }}" class="ui-card grid gap-4 p-5 sm:grid-cols-2 xl:grid-cols-5 xl:items-end">
        <label class="grid gap-2 text-sm font-semibold text-stone-700">
            Período
            <select name="period" class="min-h-11 rounded-lg border border-stone-300 bg-white px-3 text-stone-900 focus:border-brand-600 focus:outline-2 focus:outline-brand-600">
                @foreach (['today' => 'Hoy', 'last_7_days' => 'Últimos 7 días', 'this_month' => 'Este mes', 'previous_month' => 'Mes anterior', 'custom' => 'Rango personalizado'] as $value => $label)
                    <option value="{{ $value }}" @selected($period === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="grid gap-2 text-sm font-semibold text-stone-700">
            Fecha desde
            <input type="date" name="from" value="{{ $from }}" required onchange="this.form.elements.period.value='custom'" class="min-h-11 rounded-lg border border-stone-300 bg-white px-3 text-stone-900 shadow-sm focus:border-brand-600 focus:outline-2 focus:outline-brand-600">
        </label>
        <label class="grid gap-2 text-sm font-semibold text-stone-700">
            Fecha hasta
            <input type="date" name="to" value="{{ $to }}" required onchange="this.form.elements.period.value='custom'" class="min-h-11 rounded-lg border border-stone-300 bg-white px-3 text-stone-900 shadow-sm focus:border-brand-600 focus:outline-2 focus:outline-brand-600">
        </label>
        @if ($isAdministrator)
            <label class="grid gap-2 text-sm font-semibold text-stone-700">
                Odontólogo
                <select name="dentist_id" class="min-h-11 rounded-lg border border-stone-300 bg-white px-3 text-stone-900 focus:border-brand-600 focus:outline-2 focus:outline-brand-600">
                    <option value="">Todos</option>
                    @foreach ($dentists as $dentist)
                        <option value="{{ $dentist->id }}" @selected($dentistId === $dentist->id)>{{ $dentist->user?->name ?? 'Odontólogo #'.$dentist->id }}</option>
                    @endforeach
                </select>
            </label>
        @endif
        <button type="submit" class="ui-button ui-button-primary">Filtrar período</button>
    </form>

    <p class="text-sm text-slate-600">Datos del {{ \Illuminate\Support\Carbon::parse($from)->format('d/m/Y') }} al {{ \Illuminate\Support\Carbon::parse($to)->format('d/m/Y') }}. Las próximas citas y alertas de hoy muestran la agenda actual.</p>

    @php
        $cards = [
            ['label' => $isDentist || $dentistId !== null ? 'Pacientes asignados' : 'Pacientes activos', 'value' => $dashboardMetrics['active_patients'], 'context' => $isDentist ? 'Fichas autorizadas' : ($dentistId !== null ? 'Del odontólogo seleccionado' : 'En el consultorio'), 'icon' => '01'],
            ['label' => 'Pacientes registrados', 'value' => $dashboardMetrics['registered_patients'], 'context' => 'En el período filtrado', 'icon' => '02'],
            ['label' => 'Citas programadas de hoy', 'value' => $dashboardMetrics['appointments_today'], 'context' => 'Hora local del consultorio', 'icon' => '02'],
            ['label' => 'Citas programadas', 'value' => $dashboardMetrics['scheduled_appointments'], 'context' => 'En el período filtrado', 'icon' => '03'],
        ];

        if ($isAdministrator) {
            $cards[] = ['label' => 'Tratamientos realizados', 'value' => $dashboardMetrics['treatments_completed'], 'context' => 'En el período filtrado', 'icon' => '03'];
            $cards[] = ['label' => 'Ingresos registrados', 'value' => 'Q '.number_format((float) $dashboardMetrics['registered_income'], 2), 'context' => 'Pagos odontológicos del período', 'icon' => '04'];
            $cards[] = ['label' => 'Planes activos', 'value' => $dashboardMetrics['active_plans'], 'context' => 'Creados en el período', 'icon' => '05'];
        } elseif ($isDentist) {
            $cards[] = ['label' => 'Tratamientos realizados', 'value' => $dashboardMetrics['treatments_completed'], 'context' => 'En el período filtrado', 'icon' => '03'];
            $cards[] = ['label' => 'Citas confirmadas', 'value' => $dashboardMetrics['confirmed_appointments'], 'context' => 'En el período filtrado', 'icon' => '04'];
            $cards[] = ['label' => 'Planes activos', 'value' => $dashboardMetrics['active_plans'], 'context' => 'Creados en el período', 'icon' => '05'];
        } else {
            $cards[] = ['label' => 'Citas por confirmar', 'value' => $dashboardMetrics['pending_appointments'], 'context' => 'Próximas 24 horas', 'icon' => '03'];
            $cards[] = ['label' => 'Cobros pendientes', 'value' => $dashboardMetrics['pending_invoice_count'], 'context' => 'Facturas emitidas en el período con saldo', 'icon' => '04'];
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

    @if ($isAdministrator)
        <section class="ui-card p-5 sm:p-6" aria-label="Resumen financiero del período">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div><h2 class="text-lg font-bold text-coffee">Resumen financiero</h2><p class="text-sm text-slate-600">Importes del consultorio completo; el filtro de odontólogo solo afecta la actividad clínica.</p></div>
                <a href="{{ route('facturacion.reportes.index', ['from' => $from, 'to' => $to]) }}" class="ui-button ui-button-secondary">Ver reporte financiero</a>
            </div>
            <dl class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl bg-brand-50 p-4"><dt class="text-sm text-brand-800">Total facturado</dt><dd class="mt-2 text-xl font-bold tabular-nums">Q {{ number_format((float) $dashboardMetrics['issued_total'], 2) }}</dd></div>
                <div class="rounded-xl bg-brand-50 p-4"><dt class="text-sm text-brand-800">Total cobrado</dt><dd class="mt-2 text-xl font-bold tabular-nums">Q {{ number_format((float) $dashboardMetrics['received_total'], 2) }}</dd></div>
                <div class="rounded-xl bg-brand-50 p-4"><dt class="text-sm text-brand-800">Saldo pendiente</dt><dd class="mt-2 text-xl font-bold tabular-nums">Q {{ number_format((float) $dashboardMetrics['pending_balance'], 2) }}</dd></div>
                <div class="rounded-xl bg-brand-50 p-4"><dt class="text-sm text-brand-800">Facturas anuladas</dt><dd class="mt-2 text-xl font-bold tabular-nums">{{ $dashboardMetrics['cancelled_invoice_count'] }}</dd></div>
            </dl>
            <p class="mt-3 text-xs text-slate-600">Facturado y pendiente: facturas vigentes emitidas en el período. Cobrado: pagos vigentes recibidos en el período. Anuladas: cancelaciones registradas en el período.</p>
        </section>
    @endif

    <section class="grid gap-6 xl:grid-cols-2" aria-label="Gráficas y reportes del período">
        <article class="ui-card p-5 sm:p-6">
            <h2 class="text-lg font-bold text-coffee">Citas por día y estado</h2>
            <p class="mt-1 text-sm text-slate-600">Fecha local del consultorio.</p>
            @php
                $maxAppointments = max(1, (int) $dashboardMetrics['appointment_trend']->max('aggregate'));
            @endphp
            <div class="mt-4 grid max-h-80 gap-3 overflow-y-auto">
                @forelse ($dashboardMetrics['appointment_trend'] as $row)
                    <div class="grid grid-cols-[6rem_1fr_2rem] items-center gap-3 text-sm">
                        <span><time datetime="{{ $row->day }}">{{ \Illuminate\Support\Carbon::parse($row->day)->format('d/m') }}</time><span class="block text-xs text-slate-600">{{ $row->status->label() }}</span></span>
                        <progress value="{{ $row->aggregate }}" max="{{ $maxAppointments }}" class="h-3 w-full accent-brand-600" aria-label="{{ $row->status->label() }} el {{ $row->day }}"></progress>
                        <strong class="text-right tabular-nums">{{ $row->aggregate }}</strong>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No hay citas en este período.</p>
                @endforelse
            </div>
        </article>

        @if ($isAdministrator || $isDentist)
            <article class="ui-card p-5 sm:p-6">
                <h2 class="text-lg font-bold text-coffee">Procedimientos realizados por día</h2>
                <p class="mt-1 text-sm text-slate-600">Solo procedimientos registrados hasta ahora.</p>
                @php
                    $maxProcedures = max(1, (int) $dashboardMetrics['treatment_trend']->max('aggregate'));
                @endphp
                <div class="mt-4 grid max-h-80 gap-3 overflow-y-auto">
                    @forelse ($dashboardMetrics['treatment_trend'] as $row)
                        <div class="grid grid-cols-[5rem_1fr_2rem] items-center gap-3 text-sm">
                            <time datetime="{{ $row->day }}">{{ \Illuminate\Support\Carbon::parse($row->day)->format('d/m/Y') }}</time>
                            <progress value="{{ $row->aggregate }}" max="{{ $maxProcedures }}" class="h-3 w-full accent-brand-600" aria-label="Procedimientos del {{ $row->day }}"></progress>
                            <strong class="text-right tabular-nums">{{ $row->aggregate }}</strong>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No hay procedimientos realizados en este período.</p>
                    @endforelse
                </div>
            </article>

            <article class="ui-card p-5 sm:p-6">
                <h2 class="text-lg font-bold text-coffee">Tratamientos más frecuentes</h2>
                <p class="mt-1 text-sm text-slate-600">Hasta cinco procedimientos realizados.</p>
                @php
                    $maxTreatments = max(1, (int) $dashboardMetrics['top_treatments']->max('aggregate'));
                @endphp
                <div class="mt-4 grid gap-3">
                    @forelse ($dashboardMetrics['top_treatments'] as $row)
                        <div class="grid grid-cols-[minmax(0,1fr)_5rem] items-center gap-3 text-sm">
                            <div class="min-w-0"><span class="block truncate font-medium">{{ $row->treatment?->name }}</span><progress value="{{ $row->aggregate }}" max="{{ $maxTreatments }}" class="mt-1 h-3 w-full accent-brand-600" aria-label="{{ $row->treatment?->name }}"></progress></div>
                            <strong class="text-right tabular-nums">{{ $row->aggregate }}</strong>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No hay tratamientos realizados en este período.</p>
                    @endforelse
                </div>
            </article>
        @endif

        @if ($isAdministrator)
            <article class="ui-card p-5 sm:p-6">
                <h2 class="text-lg font-bold text-coffee">Ingresos cobrados por día</h2>
                <p class="mt-1 text-sm text-slate-600">Pagos registrados; se excluyen pagos anulados.</p>
                @php
                    $maxRevenue = max(1, (int) $dashboardMetrics['revenue_by_day']->max('amount_cents'));
                @endphp
                <div class="mt-4 grid max-h-80 gap-3 overflow-y-auto">
                    @forelse ($dashboardMetrics['revenue_by_day'] as $row)
                        <div class="grid grid-cols-[5rem_1fr_6rem] items-center gap-3 text-sm">
                            <time datetime="{{ $row->payment_date->toDateString() }}">{{ $row->payment_date->format('d/m/Y') }}</time>
                            <progress value="{{ $row->amount_cents }}" max="{{ $maxRevenue }}" class="h-3 w-full accent-brand-600" aria-label="Ingresos del {{ $row->payment_date->toDateString() }}"></progress>
                            <strong class="text-right tabular-nums">Q {{ number_format($row->amount_cents / 100, 2) }}</strong>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No hay pagos registrados en este período.</p>
                    @endforelse
                </div>
            </article>

            <article class="ui-card p-5 sm:p-6">
                <h2 class="text-lg font-bold text-coffee">Atención por odontólogo</h2>
                <p class="mt-1 text-sm text-slate-600">Hasta diez odontólogos con actividad en el período.</p>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-left text-sm"><thead><tr class="border-b border-stone-200"><th scope="col" class="py-2">Odontólogo</th><th scope="col" class="py-2 text-right">Citas</th><th scope="col" class="py-2 text-right">Procedimientos</th></tr></thead><tbody>
                        @forelse ($dashboardMetrics['dentist_activity'] as $dentist)
                            <tr class="border-b border-stone-100"><th scope="row" class="py-2 font-medium">{{ $dentist->user?->name ?? 'Odontólogo #'.$dentist->id }}</th><td class="py-2 text-right tabular-nums">{{ $dentist->appointment_count }}</td><td class="py-2 text-right tabular-nums">{{ $dentist->procedure_count }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="py-4 text-slate-500">No hay actividad de odontólogos en este período.</td></tr>
                        @endforelse
                    </tbody></table>
                </div>
            </article>
        @endif
    </section>

    <section class="grid gap-6 lg:grid-cols-2" aria-label="Resumen del consultorio">
        <article class="ui-card p-5 sm:p-6">
            <h2 class="text-lg font-bold text-coffee">Resumen de estados</h2>
            <p class="mt-1 text-sm text-slate-600">Citas del período seleccionado.</p>
            <dl class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3">
                <div class="rounded-xl bg-brand-50 p-4"><dt class="text-xs font-semibold text-brand-800">Programadas</dt><dd class="mt-2 text-2xl font-bold tabular-nums text-coffee">{{ $dashboardMetrics['scheduled_appointments'] }}</dd></div>
                <div class="rounded-xl bg-brand-50 p-4"><dt class="text-xs font-semibold text-brand-800">Confirmadas</dt><dd class="mt-2 text-2xl font-bold tabular-nums text-coffee">{{ $dashboardMetrics['confirmed_appointments'] }}</dd></div>
                <div class="rounded-xl bg-brand-50 p-4"><dt class="text-xs font-semibold text-brand-800">Completadas</dt><dd class="mt-2 text-2xl font-bold tabular-nums text-coffee">{{ $dashboardMetrics['completed_appointments'] }}</dd></div>
                <div class="rounded-xl bg-brand-50 p-4"><dt class="text-xs font-semibold text-brand-800">Canceladas</dt><dd class="mt-2 text-2xl font-bold tabular-nums text-coffee">{{ $dashboardMetrics['cancelled_appointments'] }}</dd></div>
                @if ($isAdministrator)
                    <div class="rounded-xl bg-brand-50 p-4"><dt class="text-xs font-semibold text-brand-800">Odontólogos activos</dt><dd class="mt-2 text-2xl font-bold tabular-nums text-coffee">{{ $dashboardMetrics['active_dentists'] }}</dd></div>
                    <div class="rounded-xl bg-brand-50 p-4"><dt class="text-xs font-semibold text-brand-800">Facturas emitidas</dt><dd class="mt-2 text-2xl font-bold tabular-nums text-coffee">{{ $dashboardMetrics['issued_invoice_count'] }}</dd></div>
                @endif
                @if ($isAdministrator || $isReceptionist)
                    <div class="rounded-xl bg-brand-50 p-4"><dt class="text-xs font-semibold text-brand-800">Saldos pendientes del período</dt><dd class="mt-2 break-words text-xl font-bold tabular-nums text-coffee">Q {{ number_format((float) $dashboardMetrics['pending_balance'], 2) }}</dd><a href="{{ route('facturacion.facturas.index', ['balance' => 'pending', 'from' => $from, 'to' => $to]) }}" class="mt-2 inline-block text-xs font-semibold text-brand-700 underline">Ver cuentas pendientes</a></div>
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
            @can('view-appointment-reminders')
                <a href="{{ route('recordatorios.index') }}" class="mt-4 inline-flex font-semibold text-brand-700 underline underline-offset-2">Ver recordatorios</a>
            @endcan
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
