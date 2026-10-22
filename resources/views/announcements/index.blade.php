@extends('layouts.admin')

@section('title', 'Anuncios')
@section('heading', 'Anuncios')
@section('subheading', 'Publicaciones visibles en el sitio público')

@section('actions')
    <a href="{{ route('anuncios.create') }}" class="ui-button ui-button-primary">Nuevo anuncio</a>
@endsection

@section('content')
    <div class="ui-table-wrap">
        <div class="ui-table-scroll">
            <table class="w-full min-w-[42rem] text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-slate-700">
                        <th scope="col" class="p-4">Título</th>
                        <th scope="col" class="p-4">Vigencia</th>
                        <th scope="col" class="p-4">Orden</th>
                        <th scope="col" class="p-4">Estado</th>
                        <th scope="col" class="p-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($announcements as $announcement)
                        <tr class="border-b border-slate-100 last:border-0">
                            <td class="p-4 font-semibold text-charcoal">{{ $announcement->title }}</td>
                            <td class="p-4 text-slate-700">{{ $announcement->published_on?->format('d/m/Y') ?? 'Sin fecha' }} — {{ $announcement->expires_on?->format('d/m/Y') ?? 'Sin fin' }}</td>
                            <td class="p-4 text-slate-700">{{ $announcement->sort_order }}</td>
                            <td class="p-4"><span class="ui-badge {{ $announcement->is_published ? 'ui-badge-success' : 'ui-badge-neutral' }}">{{ $announcement->is_published ? 'Publicado' : 'Borrador' }}</span></td>
                            <td class="p-4">
                                <div class="flex justify-end gap-2">
                                    <a class="ui-button ui-button-compact ui-button-secondary" href="{{ route('anuncios.edit', $announcement) }}">Editar</a>
                                    <form method="POST" action="{{ route('anuncios.toggle', $announcement) }}" @if ($announcement->is_published) data-confirm="¿Despublicar {{ $announcement->title }}?" @endif>
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="ui-button ui-button-compact {{ $announcement->is_published ? 'ui-button-danger' : 'ui-button-secondary' }}">{{ $announcement->is_published ? 'Despublicar' : 'Publicar' }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-8 text-center text-slate-600">No hay anuncios. Crea uno para mostrar novedades en el sitio público.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($announcements->hasPages())
            <div class="border-t border-slate-200 px-4 py-3">{{ $announcements->links() }}</div>
        @endif
    </div>
@endsection
