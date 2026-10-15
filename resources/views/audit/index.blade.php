@extends('layouts.admin')

@section('title', 'Auditoría del sistema')
@section('heading', 'Auditoría del sistema')
@section('subheading', 'Eventos registrados con los filtros seleccionados. Fechas en America/Guatemala.')

@section('content')
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
        @foreach (['total' => 'Eventos', 'security' => 'Seguridad', 'permissions' => 'Permisos', 'clinical' => 'Clínica', 'financial' => 'Finanzas'] as $key => $label)
            <div class="ui-card p-4"><p class="text-sm text-stone-600">{{ $label }}</p><p class="text-2xl font-bold">{{ $summary->$key ?? 0 }}</p></div>
        @endforeach
    </div>

    <form method="GET" action="{{ route('audit.index') }}" class="ui-card grid gap-4 p-5 sm:grid-cols-2 xl:grid-cols-4">
        <label class="grid gap-1 text-sm font-semibold">Buscar<input name="search" type="search" maxlength="100" value="{{ $filters['search'] ?? '' }}" class="w-full" placeholder="Acción, módulo o usuario"></label>
        <label class="grid gap-1 text-sm font-semibold">Usuario<select name="user_id" class="w-full"><option value="">Todos</option>@foreach ($users as $user)<option value="{{ $user->id }}" @selected((string) ($filters['user_id'] ?? '') === (string) $user->id)>{{ $user->name }}</option>@endforeach</select></label>
        <label class="grid gap-1 text-sm font-semibold">Rol<select name="actor_role" class="w-full"><option value="">Todos</option>@foreach ([\App\Models\Role::ADMINISTRATOR, \App\Models\Role::RECEPTIONIST, \App\Models\Role::DENTIST, 'Sistema'] as $role)<option value="{{ $role }}" @selected(($filters['actor_role'] ?? '') === $role)>{{ $role }}</option>@endforeach</select></label>
        <label class="grid gap-1 text-sm font-semibold">Módulo<select name="module" class="w-full"><option value="">Todos</option>@foreach (['security', 'patients', 'appointments', 'appointment_requests', 'clinical_records', 'finances', 'public_site'] as $module)<option value="{{ $module }}" @selected(($filters['module'] ?? '') === $module)>{{ $module }}</option>@endforeach</select></label>
        <label class="grid gap-1 text-sm font-semibold">Acción<select name="action" class="w-full"><option value="">Todas</option>@foreach ($actions as $action)<option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ $action }}</option>@endforeach</select></label>
        <label class="grid gap-1 text-sm font-semibold">Resultado<select name="result" class="w-full"><option value="">Todos</option>@foreach (['success' => 'Exitoso', 'rejected' => 'Rechazado', 'failed' => 'Fallido'] as $value => $label)<option value="{{ $value }}" @selected(($filters['result'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></label>
        <label class="grid gap-1 text-sm font-semibold">Desde<input name="from" type="date" value="{{ $filters['from'] ?? '' }}" class="w-full"></label>
        <label class="grid gap-1 text-sm font-semibold">Hasta<input name="to" type="date" value="{{ $filters['to'] ?? '' }}" class="w-full"></label>
        <div class="flex gap-2 sm:col-span-2 xl:col-span-4"><button class="ui-button ui-button-primary">Filtrar</button><a href="{{ route('audit.index') }}" class="ui-button ui-button-secondary">Limpiar</a></div>
    </form>

    <div class="ui-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[65rem] text-left text-sm">
                <thead class="bg-stone-50 text-xs font-semibold uppercase text-stone-600"><tr><th class="p-3">Fecha y hora</th><th class="p-3">Usuario</th><th class="p-3">Rol</th><th class="p-3">Módulo</th><th class="p-3">Acción</th><th class="p-3">Resultado</th><th class="p-3">Detalles</th></tr></thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($logs as $log)
                        <tr><td class="p-3 whitespace-nowrap">{{ $log->created_at->setTimezone('America/Guatemala')->format('d/m/Y H:i:s') }}</td><td class="p-3">{{ $log->actor?->name ?? ($log->actor_user_id ? 'Usuario #'.$log->actor_user_id : 'Sistema') }}</td><td class="p-3">{{ $log->actor_role }}</td><td class="p-3">{{ $log->module }}</td><td class="p-3">{{ $log->action }}</td><td class="p-3">{{ ['success' => 'Exitoso', 'rejected' => 'Rechazado', 'failed' => 'Fallido'][$log->result] ?? $log->result }}</td><td class="p-3"><a href="{{ route('audit.show', $log) }}" class="font-semibold text-brand-700 hover:underline">Ver evento</a></td></tr>
                    @empty
                        <tr><td colspan="7" class="p-6 text-center text-stone-600">No hay eventos para estos filtros.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-stone-200 p-4">{{ $logs->links() }}</div>
    </div>
@endsection
