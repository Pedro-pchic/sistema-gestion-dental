@extends('layouts.admin')

@section('title', 'Permisos de '.$role->name)
@section('heading', 'Permisos de '.$role->name)
@section('subheading', 'Los permisos generales se aplican junto con las restricciones clínicas y financieras existentes.')
@section('actions')
    <a href="{{ route('roles.index') }}" class="ui-button ui-button-secondary">Volver a roles</a>
@endsection

@section('content')
    <form method="POST" action="{{ route('roles.update', $role) }}" class="flex flex-col gap-5">
        @csrf
        @method('PUT')
        @foreach ($groups as $module => $permissions)
            <fieldset class="ui-card p-5">
                <legend class="px-1 text-base font-bold">{{ $module }}</legend>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($permissions as $name => $label)
                        <label class="flex items-start gap-3 rounded-lg border border-stone-200 p-3 text-sm hover:bg-stone-50">
                            <input type="checkbox" name="permissions[]" value="{{ $name }}" @checked(in_array($name, old('permissions', $role->effectivePermissionNames()), true)) class="mt-0.5 size-4 accent-brand-700">
                            <span><span class="block font-semibold">{{ $label }}</span><span class="text-xs text-stone-500">{{ $name }}</span></span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
        @endforeach

        @can('roles.manage')
            <div class="ui-card flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
                <label class="flex items-start gap-3 text-sm font-medium">
                    <input type="checkbox" name="confirm_changes" value="1" required class="mt-0.5 size-4 accent-brand-700">
                    Confirmo que revisé los cambios de acceso de este rol.
                </label>
                <div class="flex gap-2">
                    <button type="reset" class="ui-button ui-button-secondary">Restablecer</button>
                    <button type="submit" class="ui-button ui-button-primary">Guardar permisos</button>
                </div>
            </div>
        @endcan
    </form>
@endsection
