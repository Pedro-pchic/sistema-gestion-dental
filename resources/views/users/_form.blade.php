<div class="grid gap-5 sm:grid-cols-2">
    <div>
        <label for="name" class="block text-sm font-medium">Nombre</label>
        <input id="name" name="name" type="text" value="{{ old('name', $user->name ?? '') }}" required
            class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
    </div>

    <div>
        <label for="email" class="block text-sm font-medium">Correo electrónico</label>
        <input id="email" name="email" type="email" value="{{ old('email', $user->email ?? '') }}" required
            class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
    </div>

    <div>
        <label for="role_id" class="block text-sm font-medium">Rol</label>
        <select id="role_id" name="role_id" required
            class="mt-2 w-full rounded-lg border border-stone-300 bg-white px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
            <option value="">Selecciona un rol</option>
            @foreach ($roles as $role)
                <option value="{{ $role->id }}" @selected((string) old('role_id', $user->role_id ?? '') === (string) $role->id)>
                    {{ $role->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="flex items-end pb-2">
        <input type="hidden" name="is_active" value="0">
        <label class="flex items-center gap-2 text-sm font-medium">
            <input name="is_active" type="checkbox" value="1" @checked((bool) old('is_active', $user->is_active ?? true))
                class="rounded border-stone-300 text-brand-600 focus:ring-brand-500">
            Usuario activo
        </label>
    </div>

    <div>
        <label for="password" class="block text-sm font-medium">Contraseña</label>
        <input id="password" name="password" type="password" @required(! isset($user)) autocomplete="new-password"
            class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
        @isset($user)
            <p class="mt-1 text-xs text-stone-500">Déjala vacía para conservar la contraseña actual.</p>
        @endisset
    </div>

    <div>
        <label for="password_confirmation" class="block text-sm font-medium">Confirmar contraseña</label>
        <input id="password_confirmation" name="password_confirmation" type="password" @required(! isset($user)) autocomplete="new-password"
            class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
    </div>
</div>
