@extends('layouts.admin')

@section('title', 'Detalle del proveedor')
@section('heading', $supplier->name)
@section('subheading', 'Detalle del proveedor')

@section('actions')
    <a href="{{ route('proveedores.edit', $supplier) }}" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600">Editar proveedor</a>
@endsection

@section('content')
    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
        <dl class="grid gap-6 sm:grid-cols-2">
            <div>
                <dt class="text-sm font-medium text-stone-500">NIT</dt>
                <dd class="mt-1">{{ $supplier->nit ?? 'Sin NIT registrado' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-stone-500">Estado</dt>
                <dd class="mt-1 font-semibold">{{ $supplier->is_active ? 'Activo' : 'Inactivo' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-stone-500">Contacto</dt>
                <dd class="mt-1">{{ $supplier->contact_name ?? 'Sin contacto registrado' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-stone-500">Teléfono</dt>
                <dd class="mt-1">{{ $supplier->phone ?? 'Sin teléfono registrado' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-stone-500">Correo electrónico</dt>
                <dd class="mt-1">{{ $supplier->email ?? 'Sin correo registrado' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-stone-500">Órdenes de compra</dt>
                <dd class="mt-1">{{ $supplier->purchases_count }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-sm font-medium text-stone-500">Dirección</dt>
                <dd class="mt-1 whitespace-pre-line">{{ $supplier->address ?? 'Sin dirección registrada' }}</dd>
            </div>
        </dl>

        <div class="mt-6 flex flex-wrap gap-3 border-t border-stone-200 pt-5">
            <a href="{{ route('proveedores.index') }}" class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold hover:bg-stone-50">Volver</a>
            <form method="POST" action="{{ route('proveedores.status', $supplier) }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="rounded-lg px-4 py-2 text-sm font-semibold {{ $supplier->is_active ? 'bg-amber-100 text-amber-900 hover:bg-amber-200' : 'bg-emerald-100 text-emerald-900 hover:bg-emerald-200' }}">
                    {{ $supplier->is_active ? 'Desactivar' : 'Activar' }}
                </button>
            </form>
        </div>
    </div>
@endsection
