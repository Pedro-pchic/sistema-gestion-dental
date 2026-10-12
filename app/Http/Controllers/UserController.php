<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Dentist;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        Gate::authorize('manage-users');

        $users = User::with('role')
            ->orderBy('name')
            ->paginate(15);

        return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        Gate::authorize('manage-users');

        $roles = Role::orderBy('name')->get();

        return view('users.create', compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $data['is_active'] ?? true;
        $data['can_view_clinical_records'] = $this->canGrantClinicalAccess(
            $data['role_id'],
            $data['can_view_clinical_records'] ?? false,
        );

        $user = User::create($data);
        $this->syncDentistProfile($user);

        return redirect()->route('usuarios.index')->with('status', 'Usuario creado correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): View
    {
        Gate::authorize('manage-users');

        return view('users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user): View
    {
        Gate::authorize('manage-users');

        $roles = Role::orderBy('name')->get();

        return view('users.edit', compact('roles', 'user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        $data['can_view_clinical_records'] = $this->canGrantClinicalAccess(
            $data['role_id'],
            $data['can_view_clinical_records'] ?? false,
        );

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update($data);
        $this->syncDentistProfile($user);

        return redirect()->route('usuarios.index')->with('status', 'Usuario actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        Gate::authorize('manage-users');

        abort(405);
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        Gate::authorize('manage-users');

        $user->update(['is_active' => ! $user->is_active]);
        $this->syncDentistProfile($user);

        return redirect()->route('usuarios.index')->with('status', 'Estado del usuario actualizado.');
    }

    private function syncDentistProfile(User $user): void
    {
        if ($user->hasRole(Role::DENTIST)) {
            Dentist::updateOrCreate(
                ['user_id' => $user->id],
                ['is_active' => $user->is_active],
            );

            return;
        }

        $dentist = $user->dentist;

        if ($dentist === null) {
            return;
        }

        if (
            $dentist->appointments()->exists()
            || $dentist->treatmentProcedures()->exists()
            || $dentist->treatmentPlans()->exists()
        ) {
            $dentist->patients()->detach();
            $dentist->update(['is_active' => false]);

            return;
        }

        $dentist->delete();
    }

    private function canGrantClinicalAccess(int|string $roleId, bool|int|string $requested): bool
    {
        return (bool) $requested
            && Role::query()->whereKey($roleId)->value('name') === Role::ADMINISTRATOR;
    }
}
