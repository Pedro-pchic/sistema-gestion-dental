@extends('layouts.admin')

@section('title', 'Evento de auditoría #'.$log->id)
@section('heading', 'Evento #'.$log->id)
@section('subheading', 'Registro de solo lectura. Fecha en America/Guatemala.')
@section('actions')<a href="{{ route('audit.index') }}" class="ui-button ui-button-secondary">Volver a auditoría</a>@endsection

@section('content')
    <div class="ui-card grid gap-5 p-5 sm:grid-cols-2">
        @foreach (['Identificador' => $log->id, 'Fecha y hora' => $log->created_at->setTimezone('America/Guatemala')->format('d/m/Y H:i:s'), 'Actor' => $log->actor?->name ?? ($log->actor_user_id ? 'Usuario #'.$log->actor_user_id : 'Sistema'), 'ID de usuario' => $log->actor_user_id ?? 'Sistema', 'Rol al momento' => $log->actor_role, 'Módulo' => $log->module, 'Acción' => $log->action, 'Resultado' => $log->result, 'Tipo de recurso' => $log->auditable_type ?? '—', 'ID de recurso' => $log->auditable_id ?? '—', 'IP' => $log->ip_address ?? '—', 'ID de solicitud' => $log->request_id ?? '—'] as $label => $value)
            <div><dt class="text-xs font-semibold uppercase text-stone-500">{{ $label }}</dt><dd class="mt-1 break-all text-sm font-medium">{{ $value }}</dd></div>
        @endforeach
    </div>
    @if ($log->metadata)
        <section class="ui-card p-5" aria-labelledby="metadata-title">
            <h2 id="metadata-title" class="mb-3 font-bold">Contexto permitido</h2>
            <dl class="grid gap-3 sm:grid-cols-2">
                @foreach ($log->metadata as $key => $value)
                    <div><dt class="text-xs font-semibold text-stone-600">{{ $key }}</dt><dd class="break-all text-sm">{{ is_array($value) ? implode(', ', $value) : $value }}</dd></div>
                @endforeach
            </dl>
        </section>
    @endif
@endsection
