@extends('layouts.admin')
@section('title', 'Equipo público')
@section('heading', 'Equipo público')
@section('subheading', 'Solo los perfiles habilitados aparecen en la web')
@section('content')
<div class="grid gap-6 lg:grid-cols-2">
@forelse ($dentists as $dentist)
    <form method="POST" action="{{ route('odontologos-publicos.update', $dentist) }}" enctype="multipart/form-data" class="ui-card space-y-4 p-6">@csrf @method('PUT')
        <div><h2 class="text-lg font-bold">{{ $dentist->user->name }}</h2><p class="text-sm text-slate-500">{{ $dentist->is_active && $dentist->user->is_active ? 'Activo' : 'Inactivo' }}</p></div>
        <div><label for="specialty-{{ $dentist->id }}" class="block text-sm font-semibold">Especialidad pública</label><input id="specialty-{{ $dentist->id }}" name="specialty" value="{{ old('specialty', $dentist->specialty) }}" maxlength="255" class="mt-2 w-full"></div>
        <div><label for="bio-{{ $dentist->id }}" class="block text-sm font-semibold">Presentación</label><textarea id="bio-{{ $dentist->id }}" name="public_bio" rows="3" maxlength="1000" class="mt-2 w-full">{{ old('public_bio', $dentist->public_bio) }}</textarea></div>
        <div><label for="photo-{{ $dentist->id }}" class="block text-sm font-semibold">Fotografía opcional</label><input id="photo-{{ $dentist->id }}" name="photo" type="file" accept="image/jpeg,image/png,image/webp" class="mt-2 w-full"></div>
        <div><input type="hidden" name="is_public" value="0"><label class="flex items-center gap-2"><input type="checkbox" name="is_public" value="1" @checked($dentist->is_public)> Mostrar en la web</label></div>
        <button class="ui-button ui-button-primary">Guardar perfil</button>
    </form>
@empty <p class="ui-card p-6">No hay odontólogos.</p> @endforelse
</div>
@endsection
