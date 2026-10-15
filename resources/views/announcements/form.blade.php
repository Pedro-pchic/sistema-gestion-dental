@extends('layouts.admin')
@section('title', $announcement->exists ? 'Editar anuncio' : 'Nuevo anuncio')
@section('heading', $announcement->exists ? 'Editar anuncio' : 'Nuevo anuncio')
@section('actions')<a href="{{ route('anuncios.index') }}" class="ui-button ui-button-secondary">Volver</a>@endsection
@section('content')
<form method="POST" action="{{ $announcement->exists ? route('anuncios.update', $announcement) : route('anuncios.store') }}" enctype="multipart/form-data" class="ui-card grid max-w-3xl gap-5 p-6 sm:grid-cols-2">@csrf @if ($announcement->exists) @method('PUT') @endif
    <div class="sm:col-span-2"><label for="title" class="block text-sm font-semibold">Título</label><input id="title" name="title" value="{{ old('title', $announcement->title) }}" maxlength="255" required class="mt-2 w-full"></div>
    <div class="sm:col-span-2"><label for="description" class="block text-sm font-semibold">Descripción</label><textarea id="description" name="description" rows="5" maxlength="5000" required class="mt-2 w-full">{{ old('description', $announcement->description) }}</textarea></div>
    <div><label for="published_on" class="block text-sm font-semibold">Fecha de publicación</label><input id="published_on" name="published_on" type="date" value="{{ old('published_on', $announcement->published_on?->toDateString()) }}" class="mt-2 w-full"></div>
    <div><label for="expires_on" class="block text-sm font-semibold">Vigente hasta</label><input id="expires_on" name="expires_on" type="date" value="{{ old('expires_on', $announcement->expires_on?->toDateString()) }}" class="mt-2 w-full"></div>
    <div><label for="sort_order" class="block text-sm font-semibold">Orden</label><input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $announcement->sort_order ?? 0) }}" required class="mt-2 w-full"></div>
    <div><label for="image" class="block text-sm font-semibold">Imagen opcional</label><input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" class="mt-2 w-full"></div>
    <div class="sm:col-span-2"><input type="hidden" name="is_published" value="0"><label class="flex items-center gap-2"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $announcement->is_published))> Publicar ahora</label></div>
    <div class="sm:col-span-2"><button class="ui-button ui-button-primary">Guardar anuncio</button></div>
</form>
@endsection
