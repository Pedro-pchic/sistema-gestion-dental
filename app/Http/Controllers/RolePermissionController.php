<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Services\PermissionCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RolePermissionController extends Controller
{
    public function index(): View
    {
        Gate::authorize('roles.view');

        return view('roles.index', [
            'roles' => Role::query()->whereIn('name', Role::INITIAL_ROLES)->with('permissions')->orderBy('name')->get(),
        ]);
    }

    public function edit(Role $role): View
    {
        Gate::authorize('roles.view');
        abort_unless(in_array($role->name, Role::INITIAL_ROLES, true), 404);

        return view('roles.edit', [
            'role' => $role->load('permissions'),
            'groups' => PermissionCatalog::GROUPS,
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        Gate::authorize('roles.manage');
        abort_unless(in_array($role->name, Role::INITIAL_ROLES, true), 404);

        $data = $request->validate([
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', 'distinct', Rule::in(PermissionCatalog::names())],
            'confirm_changes' => ['accepted'],
        ]);
        $selected = $data['permissions'] ?? [];

        if ($role->name === Role::ADMINISTRATOR && array_diff(PermissionCatalog::REQUIRED_ADMIN, $selected) !== []) {
            throw ValidationException::withMessages(['permissions' => 'El rol Administrador debe conservar los permisos de recuperación.']);
        }

        if ($role->name !== Role::ADMINISTRATOR && array_intersect(PermissionCatalog::ADMIN_ONLY, $selected) !== []) {
            throw ValidationException::withMessages(['permissions' => 'Los permisos de seguridad solo pueden asignarse al rol Administrador.']);
        }

        DB::transaction(function () use ($role, $selected, $request): void {
            $lockedRole = Role::query()->whereKey($role->id)->lockForUpdate()->firstOrFail();
            $before = $lockedRole->effectivePermissionNames();
            $ids = Permission::query()->whereIn('name', $selected)->pluck('id')->all();
            $lockedRole->permissions()->sync($ids);
            $lockedRole->permissions_initialized = true;
            $lockedRole->save();

            $added = array_values(array_diff($selected, $before));
            $revoked = array_values(array_diff($before, $selected));
            if ($added !== [] || $revoked !== []) {
                AuditLog::record('security', 'role_permissions_changed', $lockedRole, metadata: ['added' => $added, 'revoked' => $revoked], actor: $request->user());
            }
        }, 3);

        return redirect()->route('roles.edit', $role)->with('status', 'Permisos guardados correctamente.');
    }
}
