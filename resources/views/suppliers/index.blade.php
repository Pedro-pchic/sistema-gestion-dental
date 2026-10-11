@extends('layouts.admin')

@section('title', 'Proveedores')
@section('heading', 'Proveedores')
@section('subheading', 'Directorio de proveedores')

@section('actions')
    <a href="{{ route('proveedores.create') }}" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600">Nuevo proveedor</a>
@endsection

@section('content')
    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-stone-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200 text-sm">
                <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-600">
                    <tr>
                        <th class="px-4 py-3">Proveedor</th>
                        <th class="px-4 py-3">NIT</th>
                        <th class="px-4 py-3">Contacto</th>
                        <th class="px-4 py-3">Correo</th>
                        <th class="px-4 py-3">Órdenes</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($suppliers as $supplier)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $supplier->name }}</td>
                            <td class="px-4 py-3 text-stone-600">{{ $supplier->nit ?? '—' }}</td>
                            <td class="px-4 py-3 text-stone-600">{{ $supplier->contact_name ?? $supplier->phone ?? '—' }}</td>
                            <td class="px-4 py-3 text-stone-600">{{ $supplier->email ?? '—' }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ $supplier->purchases_count }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset ring-current/10 {{ $supplier->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-stone-200 text-stone-700' }}">
                                    {{ $supplier->is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('proveedores.show', $supplier) }}" class="rounded-md border border-stone-300 px-3 py-1.5 font-medium hover:bg-stone-50">Ver</a>
                                    <a href="{{ route('proveedores.edit', $supplier) }}" class="rounded-md border border-stone-300 px-3 py-1.5 font-medium hover:bg-stone-50">Editar</a>
                                    <form method="POST" action="{{ route('proveedores.status', $supplier) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="rounded-md px-3 py-1.5 font-medium {{ $supplier->is_active ? 'bg-amber-100 text-amber-900 hover:bg-amber-200' : 'bg-emerald-100 text-emerald-900 hover:bg-emerald-200' }}">
                                            {{ $supplier->is_active ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-stone-500">No hay proveedores registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($suppliers->hasPages())
            <div class="border-t border-stone-200 px-4 py-3">{{ $suppliers->links() }}</div>
        @endif
    </div>
@endsection
