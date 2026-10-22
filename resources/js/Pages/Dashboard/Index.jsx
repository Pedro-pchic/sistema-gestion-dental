import { Head, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import AdminLayout from '../../Components/AdminLayout';
import { Button, Card, DataTable, EmptyState, Field, FilterPanel, Loading, Notification, SelectInput, TextInput } from '../../Components/ui';

const money = (value) => `Q ${Number(value ?? 0).toLocaleString('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
const eventLabels = { created: 'Cita programada', rescheduled: 'Cita reprogramada', confirmed: 'Cita confirmada', started: 'Atención iniciada', completed: 'Atención completada', cancelled: 'Cita cancelada', no_show: 'Inasistencia registrada' };

function MetricCard({ label, value, context }) {
    return <Card as="article" className="relative overflow-hidden p-5 sm:p-6"><span className="absolute inset-x-0 top-0 h-1 bg-brand-500" aria-hidden="true" /><p className="text-sm font-semibold text-slate-600">{label}</p><p className="mt-4 break-words text-3xl font-bold tabular-nums text-coffee">{value ?? 0}</p><p className="mt-2 text-xs text-slate-500">{context}</p></Card>;
}

function TrendCard({ title, description, rows, value, label, empty }) {
    const maximum = Math.max(1, ...rows.map((row) => Number(row[value] ?? 0)));

    return <Card as="article" className="p-5 sm:p-6"><h2 className="text-lg font-bold text-coffee">{title}</h2><p className="mt-1 text-sm text-slate-600">{description}</p><div className="mt-4 grid max-h-80 gap-3 overflow-y-auto">{rows.length ? rows.map((row, index) => <div key={`${row.day}-${row.status ?? index}`} className="grid grid-cols-[7rem_1fr_auto] items-center gap-3 text-sm"><span>{label(row)}</span><progress value={row[value]} max={maximum} className="h-3 w-full accent-brand-600" aria-label={`${title}: ${label(row)}`} /><strong className="text-right tabular-nums">{value === 'amount_cents' ? money(row[value] / 100) : row[value]}</strong></div>) : <EmptyState>{empty}</EmptyState>}</div></Card>;
}

export default function Dashboard({ dashboardMetrics: metrics, from, to, period, dentistId, dentists, urls }) {
    const role = metrics.role;
    const administrator = role === 'Administrador';
    const dentist = role === 'Dentista';
    const receptionist = role === 'Recepcionista';
    const [filters, setFilters] = useState({ period, from, to, dentist_id: dentistId ?? '' });
    const [loading, setLoading] = useState(false);
    useEffect(() => setFilters({ period, from, to, dentist_id: dentistId ?? '' }), [period, from, to, dentistId]);
    const cards = [
        { label: dentist || dentistId ? 'Pacientes asignados' : 'Pacientes activos', value: metrics.active_patients, context: dentist ? 'Fichas autorizadas' : dentistId ? 'Del odontólogo seleccionado' : 'En el consultorio' },
        { label: 'Pacientes registrados', value: metrics.registered_patients, context: 'En el período filtrado' },
        { label: 'Citas programadas de hoy', value: metrics.appointments_today, context: 'Hora local del consultorio' },
        { label: 'Citas programadas', value: metrics.scheduled_appointments, context: 'En el período filtrado' },
    ];
    if (administrator || dentist) {
        cards.push({ label: 'Tratamientos realizados', value: metrics.treatments_completed, context: 'En el período filtrado' });
        cards.push(administrator ? { label: 'Ingresos registrados', value: money(metrics.registered_income), context: 'Pagos odontológicos del período' } : { label: 'Citas confirmadas', value: metrics.confirmed_appointments, context: 'En el período filtrado' });
        cards.push({ label: 'Planes activos', value: metrics.active_plans, context: 'Creados en el período' });
    } else {
        cards.push({ label: 'Citas por confirmar', value: metrics.pending_appointments, context: 'Próximas 24 horas' });
        cards.push({ label: 'Cobros pendientes', value: metrics.pending_invoice_count, context: 'Facturas del período con saldo' });
    }

    const submitFilters = (event) => {
        event.preventDefault();
        router.get(urls.dashboard, {
            period: filters.period,
            ...(filters.period === 'custom' ? { from: filters.from, to: filters.to } : {}),
            ...(administrator && filters.dentist_id ? { dentist_id: filters.dentist_id } : {}),
        }, { preserveState: true, onStart: () => setLoading(true), onFinish: () => setLoading(false) });
    };
    const setFilter = (name, value) => setFilters((current) => ({ ...current, [name]: value, ...(name === 'from' || name === 'to' ? { period: 'custom' } : {}) }));

    return <AdminLayout title="Dashboard" subtitle="Agenda y actividad del consultorio">
        <Head title="Dashboard dental" />
        <FilterPanel><form onSubmit={submitFilters} className="grid gap-4 sm:grid-cols-2 xl:grid-cols-5 xl:items-end">
            <Field id="period" label="Período"><SelectInput id="period" value={filters.period} onChange={(event) => setFilter('period', event.target.value)}><option value="today">Hoy</option><option value="last_7_days">Últimos 7 días</option><option value="this_month">Este mes</option><option value="previous_month">Mes anterior</option><option value="custom">Rango personalizado</option></SelectInput></Field>
            <Field id="from" label="Fecha desde"><TextInput id="from" type="date" required value={filters.from} onChange={(event) => setFilter('from', event.target.value)} /></Field>
            <Field id="to" label="Fecha hasta"><TextInput id="to" type="date" required value={filters.to} onChange={(event) => setFilter('to', event.target.value)} /></Field>
            {administrator && <Field id="dentist_id" label="Odontólogo"><SelectInput id="dentist_id" value={filters.dentist_id} onChange={(event) => setFilter('dentist_id', event.target.value)}><option value="">Todos</option>{dentists.map((person) => <option key={person.id} value={person.id}>{person.name}</option>)}</SelectInput></Field>}
            <Button type="submit" disabled={loading}>{loading ? <Loading label="Filtrando" /> : 'Filtrar período'}</Button>
        </form></FilterPanel>
        <p className="text-sm text-slate-600">Datos del {from} al {to}. Las próximas citas y alertas de hoy muestran la agenda actual.</p>
        <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Indicadores principales">{cards.map((card) => <MetricCard key={card.label} {...card} />)}</section>
        {metrics.pending_appointments > 0 && <Notification>Hay {metrics.pending_appointments} citas sin confirmar dentro de las próximas 24 horas. <a href={urls.appointments} className="font-semibold underline">Revisar agenda</a></Notification>}

        {administrator && <Card className="p-5 sm:p-6" aria-label="Resumen financiero"><div className="flex flex-wrap items-center justify-between gap-3"><div><h2 className="text-lg font-bold text-coffee">Resumen financiero</h2><p className="text-sm text-slate-600">Importes del consultorio completo; el filtro de odontólogo solo afecta la actividad clínica.</p></div><a href={urls.reports} className="ui-button ui-button-secondary">Ver reporte financiero</a></div><dl className="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">{[['Total facturado', money(metrics.issued_total)], ['Total cobrado', money(metrics.received_total)], ['Saldo pendiente', money(metrics.pending_balance)], ['Facturas anuladas', metrics.cancelled_invoice_count]].map(([label, value]) => <div key={label} className="rounded-xl bg-brand-50 p-4"><dt className="text-sm text-brand-800">{label}</dt><dd className="mt-2 text-xl font-bold tabular-nums">{value}</dd></div>)}</dl><p className="mt-3 text-xs text-slate-600">Facturado y pendiente: facturas vigentes del período. Cobrado: pagos vigentes recibidos. Anuladas: cancelaciones del período.</p></Card>}

        <section className="grid gap-6 xl:grid-cols-2" aria-label="Gráficas y reportes del período">
            <TrendCard title="Citas por día y estado" description="Fecha local del consultorio." rows={metrics.appointment_trend} value="aggregate" label={(row) => `${row.day} · ${row.status}`} empty="No hay citas en este período." />
            {(administrator || dentist) && <><TrendCard title="Procedimientos realizados por día" description="Solo procedimientos registrados hasta ahora." rows={metrics.treatment_trend} value="aggregate" label={(row) => row.day} empty="No hay procedimientos realizados en este período." /><TrendCard title="Tratamientos más frecuentes" description="Hasta cinco procedimientos realizados." rows={metrics.top_treatments} value="aggregate" label={(row) => row.name ?? 'Tratamiento'} empty="No hay tratamientos realizados en este período." /></>}
            {administrator && <><TrendCard title="Ingresos cobrados por día" description="Se excluyen pagos anulados." rows={metrics.revenue_by_day} value="amount_cents" label={(row) => row.day} empty="No hay pagos registrados en este período." /><Card className="p-5 sm:p-6"><h2 className="text-lg font-bold text-coffee">Atención por odontólogo</h2><p className="mt-1 text-sm text-slate-600">Hasta diez odontólogos con actividad en el período.</p>{metrics.dentist_activity.length ? <DataTable label="Actividad de odontólogos"><thead><tr><th className="py-2">Odontólogo</th><th className="py-2 text-right">Citas</th><th className="py-2 text-right">Procedimientos</th></tr></thead><tbody>{metrics.dentist_activity.map((person) => <tr key={person.id} className="border-t border-stone-100"><th className="py-2 font-medium">{person.name}</th><td className="py-2 text-right">{person.appointment_count}</td><td className="py-2 text-right">{person.procedure_count}</td></tr>)}</tbody></DataTable> : <EmptyState>No hay actividad de odontólogos en este período.</EmptyState>}</Card></>}
        </section>

        <section className="grid gap-6 lg:grid-cols-2" aria-label="Resumen del consultorio"><Card className="p-5 sm:p-6"><h2 className="text-lg font-bold text-coffee">Resumen de estados</h2><p className="mt-1 text-sm text-slate-600">Citas del período seleccionado.</p><dl className="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3">{[['Programadas', metrics.scheduled_appointments], ['Confirmadas', metrics.confirmed_appointments], ['Completadas', metrics.completed_appointments], ['Canceladas', metrics.cancelled_appointments], ...(administrator ? [['Odontólogos activos', metrics.active_dentists], ['Facturas emitidas', metrics.issued_invoice_count]] : []), ...(administrator || receptionist ? [['Saldos pendientes', money(metrics.pending_balance)]] : [])].map(([label, value]) => <div key={label} className="rounded-xl bg-brand-50 p-4"><dt className="text-xs font-semibold text-brand-800">{label}</dt><dd className="mt-2 break-words text-xl font-bold tabular-nums text-coffee">{value}</dd></div>)}</dl>{(administrator || receptionist) && <a href={`${urls.invoices}?balance=pending&from=${from}&to=${to}`} className="mt-3 inline-block font-semibold text-brand-700 underline">Ver cuentas pendientes</a>}</Card>
            <Card className="overflow-hidden"><div className="border-b border-slate-100 px-5 py-4"><h2 className="text-lg font-bold text-coffee">Actividad reciente</h2><p className="mt-1 text-sm text-slate-600">Movimientos de la agenda, sin notas clínicas.</p></div>{metrics.recent_activity.length ? <div className="divide-y divide-slate-100">{metrics.recent_activity.map((activity, index) => <div key={`${activity.appointment_id}-${index}`} className="flex items-start justify-between gap-3 px-5 py-3"><div><p className="font-semibold text-coffee">{eventLabels[activity.event_type] ?? 'Cita actualizada'}</p><p className="text-xs text-slate-600">{activity.patient}</p></div><time className="shrink-0 text-xs text-slate-500">{activity.when}</time></div>)}</div> : <EmptyState>Todavía no hay actividad de citas.</EmptyState>}</Card></section>

        <section className="grid gap-6 xl:grid-cols-3"><Card className="overflow-hidden xl:col-span-2"><div className="flex flex-wrap items-start justify-between gap-3 border-b border-stone-200 px-5 py-4"><div><h2 className="text-lg font-bold text-coffee">Próximas citas</h2><p className="mt-1 text-sm text-slate-600">Agenda de los siguientes siete días.</p></div><a href={urls.appointments} className="ui-button ui-button-secondary">Abrir agenda</a></div>{metrics.upcoming_appointments.length ? <div className="divide-y divide-stone-100">{metrics.upcoming_appointments.map((appointment) => <a key={appointment.id} href={appointment.url} className="flex flex-wrap items-center justify-between gap-3 px-5 py-4 hover:bg-stone-50"><span><strong className="block">{appointment.patient}</strong><span className="text-sm text-stone-600">{appointment.when}</span></span><span className="rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-800 ring-1 ring-brand-200">{appointment.status}</span></a>)}</div> : <EmptyState>No hay citas próximas para este perfil.</EmptyState>}</Card>
            <Card className="p-5"><h2 className="text-lg font-bold text-coffee">Recordatorios</h2><p className="mt-1 text-sm text-slate-600">Canales y cola de entrega.</p><dl className="mt-4 divide-y divide-stone-100">{Object.entries({ Correo: metrics.notification_status.email, SMS: metrics.notification_status.sms, Procesamiento: metrics.notification_status.queue, ...(metrics.notification_status.pending !== undefined ? { 'En espera': metrics.notification_status.pending, 'Fallidos, 7 días': metrics.notification_status.failed_last_7_days } : {}) }).map(([label, value]) => <div key={label} className="flex justify-between gap-4 py-3 text-sm"><dt>{label}</dt><dd className="font-semibold">{value}</dd></div>)}</dl>{metrics.notification_status.email === 'Solo registro local' && <Notification>El correo está en modo de registro local; no se entrega a pacientes.</Notification>}{urls.reminders && <a href={urls.reminders} className="mt-4 inline-flex font-semibold text-brand-700 underline">Ver recordatorios</a>}</Card></section>

        {metrics.pending_invoices?.length > 0 && <Card className="overflow-hidden"><div className="flex flex-wrap items-center justify-between gap-3 border-b border-stone-200 px-5 py-4"><div><h2 className="text-lg font-bold text-coffee">Saldos que requieren seguimiento</h2><p className="text-sm text-stone-600">Facturas odontológicas con saldo pendiente.</p></div><a href={urls.payments} className="ui-button ui-button-secondary">Ver cobros</a></div><DataTable label="Saldos pendientes"><thead><tr><th className="px-5 py-3">Paciente</th><th className="px-5 py-3">Factura</th><th className="px-5 py-3 text-right">Saldo</th></tr></thead><tbody>{metrics.pending_invoices.map((invoice) => <tr key={invoice.number} className="border-t border-stone-100"><td className="px-5 py-3">{invoice.patient}</td><td className="px-5 py-3"><a href={invoice.url} className="font-semibold text-brand-700 underline">{invoice.number}</a></td><td className="px-5 py-3 text-right font-semibold">{money(invoice.balance_due)}</td></tr>)}</tbody></DataTable></Card>}
        <nav className="ui-card flex flex-wrap gap-3 p-5" aria-label="Accesos rápidos"><a href={urls.patients} className="ui-button ui-button-secondary">Pacientes</a><a href={urls.appointments} className="ui-button ui-button-secondary">Citas</a>{urls.invoices && <a href={urls.invoices} className="ui-button ui-button-secondary">Facturación</a>}{urls.payments && <a href={urls.payments} className="ui-button ui-button-secondary">Cobros</a>}{urls.reports && <a href={urls.reports} className="ui-button ui-button-secondary">Reportes financieros</a>}</nav>
    </AdminLayout>;
}
