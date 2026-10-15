<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('audit.view');

        $filters = $request->validate([
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'actor_role' => ['nullable', Rule::in([...Role::INITIAL_ROLES, 'Sistema'])],
            'module' => ['nullable', Rule::in(['security', 'patients', 'appointments', 'appointment_requests', 'clinical_records', 'finances', 'public_site'])],
            'action' => ['nullable', 'string', 'max:80'],
            'result' => ['nullable', Rule::in(['success', 'rejected', 'failed'])],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $query = AuditLog::query()
            ->when($filters['user_id'] ?? null, fn ($query, $id) => $query->where('actor_user_id', $id))
            ->when($filters['actor_role'] ?? null, fn ($query, $role) => $query->where('actor_role', $role))
            ->when($filters['module'] ?? null, fn ($query, $module) => $query->where('module', $module))
            ->when($filters['action'] ?? null, fn ($query, $action) => $query->where('action', $action))
            ->when($filters['result'] ?? null, fn ($query, $result) => $query->where('result', $result))
            ->when($filters['search'] ?? null, function ($query, $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('action', 'like', '%'.$search.'%')
                        ->orWhere('module', 'like', '%'.$search.'%')
                        ->orWhereHas('actor', fn ($query) => $query->where('name', 'like', '%'.$search.'%'));
                });
            });

        if (isset($filters['from'])) {
            $query->where('created_at', '>=', CarbonImmutable::createFromFormat('!Y-m-d', $filters['from'], 'America/Guatemala')->utc());
        }
        if (isset($filters['to'])) {
            $query->where('created_at', '<', CarbonImmutable::createFromFormat('!Y-m-d', $filters['to'], 'America/Guatemala')->addDay()->utc());
        }

        $summary = (clone $query)->selectRaw("COUNT(*) AS total, SUM(CASE WHEN module = 'security' THEN 1 ELSE 0 END) AS security, SUM(CASE WHEN action = 'role_permissions_changed' THEN 1 ELSE 0 END) AS permissions, SUM(CASE WHEN module = 'clinical_records' THEN 1 ELSE 0 END) AS clinical, SUM(CASE WHEN module = 'finances' THEN 1 ELSE 0 END) AS financial")->first();

        return view('audit.index', [
            'logs' => $query->with('actor:id,name')->latest('id')->paginate(25)->withQueryString(),
            'summary' => $summary,
            'filters' => $filters,
            'users' => User::query()->whereHas('auditLogs')->orderBy('name')->get(['id', 'name']),
            'actions' => AuditLog::query()->distinct()->orderBy('action')->pluck('action'),
        ]);
    }

    public function show(AuditLog $auditLog): View
    {
        Gate::authorize('audit.view');

        return view('audit.show', ['log' => $auditLog->load('actor:id,name')]);
    }
}
