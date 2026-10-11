@extends('layouts.admin')

@section('title', 'Detalle del cliente')
@section('heading', $customer->name)
@section('subheading', 'Datos comerciales y direcciones')

@section('actions')
    <div class="flex flex-wrap gap-3">
        <a href="{{ route('clientes.edit', $customer) }}" class="rounded-lg border border-stone-300 bg-white px-4 py-2 text-sm font-semibold hover:bg-stone-50">Editar cliente</a>
        <a href="{{ route('clientes.direcciones.create', $customer) }}" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600">Agregar dirección</a>
        @if ($customer->is_active)
            <a href="{{ route('cotizaciones.create') }}" class="rounded-lg bg-stone-900 px-4 py-2 text-sm font-semibold text-white hover:bg-stone-700">Nueva cotización</a>
        @endif
    </div>
@endsection

@section('content')
    <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
        <dl class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <dt class="text-sm font-medium text-stone-500">NIT</dt>
                <dd class="mt-1">{{ $customer->nit ?? 'Sin NIT registrado' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-stone-500">Teléfono</dt>
                <dd class="mt-1">{{ $customer->phone ?? 'Sin teléfono registrado' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-stone-500">Correo electrónico</dt>
                <dd class="mt-1">{{ $customer->email ?? 'Sin correo registrado' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-stone-500">Estado</dt>
                <dd class="mt-1 font-semibold">{{ $customer->is_active ? 'Activo' : 'Inactivo' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-stone-500">Cotizaciones</dt>
                <dd class="mt-1">{{ $customer->quotes_count }}</dd>
            </div>
        </dl>

        <div class="mt-6 flex flex-wrap gap-3 border-t border-stone-200 pt-5">
            <a href="{{ route('clientes.index') }}" class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold hover:bg-stone-50">Volver</a>
            <form method="POST" action="{{ route('clientes.status', $customer) }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="rounded-lg px-4 py-2 text-sm font-semibold {{ $customer->is_active ? 'bg-amber-100 text-amber-900 hover:bg-amber-200' : 'bg-emerald-100 text-emerald-900 hover:bg-emerald-200' }}">
                    {{ $customer->is_active ? 'Desactivar' : 'Activar' }}
                </button>
            </form>
        </div>
    </section>

    <section class="flex flex-col gap-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold">Direcciones</h2>
                <p class="text-sm text-stone-600">Una sola dirección puede estar marcada como predeterminada.</p>
            </div>
            <a href="{{ route('clientes.direcciones.create', $customer) }}" class="rounded-lg border border-stone-300 bg-white px-4 py-2 text-sm font-semibold hover:bg-stone-50">Agregar dirección</a>
        </div>

        @forelse ($customer->addresses as $address)
            <article class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-stone-200">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="font-semibold">{{ $address->label ?? 'Dirección' }}</h3>
                            @if ($address->is_default)
                                <span class="rounded-full bg-brand-100 px-2.5 py-1 text-xs font-semibold text-brand-800">Predeterminada</span>
                            @endif
                        </div>
                        <p class="mt-2 whitespace-pre-line text-sm text-stone-700">{{ $address->address }}</p>
                        <p class="mt-1 text-sm text-stone-500">{{ collect([$address->city, $address->department])->filter()->join(', ') ?: 'Sin municipio o departamento' }}</p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('clientes.direcciones.edit', [$customer, $address]) }}" class="font-semibold text-brand-700 hover:text-brand-500">Editar</a>
                        <form method="POST" action="{{ route('clientes.direcciones.destroy', [$customer, $address]) }}" onsubmit="return confirm('¿Eliminar esta dirección?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="font-semibold text-red-700 hover:text-red-500">Eliminar</button>
                        </form>
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-xl bg-white p-8 text-center text-sm text-stone-500 shadow-sm ring-1 ring-stone-200">Este cliente todavía no tiene direcciones.</div>
        @endforelse
    </section>
@endsection
