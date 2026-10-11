@extends('layouts.admin')

@section('title', 'Categorías')
@section('heading', 'Categorías')
@section('subheading', 'Organización del catálogo de productos')

@section('actions')
    <a href="{{ route('categorias.create') }}" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600">Nueva categoría</a>
@endsection

@section('content')
    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-stone-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200 text-sm">
                <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-600">
                    <tr>
                        <th class="px-4 py-3">Nombre</th>
                        <th class="px-4 py-3">Descripción</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($categories as $category)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $category->name }}</td>
                            <td class="max-w-md px-4 py-3 text-stone-600">{{ $category->description ?? 'Sin descripción' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset ring-current/10 {{ $category->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-stone-200 text-stone-700' }}">
                                    {{ $category->is_active ? 'Activa' : 'Inactiva' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('categorias.show', $category) }}" class="rounded-md border border-stone-300 px-3 py-1.5 font-medium hover:bg-stone-50">Ver</a>
                                    <a href="{{ route('categorias.edit', $category) }}" class="rounded-md border border-stone-300 px-3 py-1.5 font-medium hover:bg-stone-50">Editar</a>
                                    <form method="POST" action="{{ route('categorias.status', $category) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="rounded-md px-3 py-1.5 font-medium {{ $category->is_active ? 'bg-amber-100 text-amber-900 hover:bg-amber-200' : 'bg-emerald-100 text-emerald-900 hover:bg-emerald-200' }}">
                                            {{ $category->is_active ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-stone-500">No hay categorías registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($categories->hasPages())
            <div class="border-t border-stone-200 px-4 py-3">{{ $categories->links() }}</div>
        @endif
    </div>
@endsection
