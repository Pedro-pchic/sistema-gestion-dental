<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Dentist;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
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

        $user = User::create($data);
        $this->syncDentistProfile($user);

        return redirect()->route('usuarios.index')->with('status', 'Usuario creado correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): View
    {
        return view('users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user): View
    {
        $roles = Role::orderBy('name')->get();

        return view('users.edit', compact('roles', 'user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

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
        abort(405);
    }

    public function toggleStatus(User $user): RedirectResponse
    {
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

        if ($dentist->appointments()->exists()) {
            $dentist->patients()->detach();
            $dentist->update(['is_active' => false]);

            return;
        }

        $dentist->delete();
    }
}
