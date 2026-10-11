@extends('layouts.admin')

@section('title', 'Clientes')
@section('heading', 'Clientes')
@section('subheading', 'Administración de clientes y contactos comerciales')

@section('actions')
    <a href="{{ route('clientes.create') }}" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600">Nuevo cliente</a>
@endsection

@section('content')
    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-stone-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200 text-sm">
                <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">
                    <tr>
                        <th class="px-5 py-3">Cliente</th>
                        <th class="px-5 py-3">NIT</th>
                        <th class="px-5 py-3">Teléfono</th>
                        <th class="px-5 py-3">Direcciones</th>
                        <th class="px-5 py-3">Cotizaciones</th>
                        <th class="px-5 py-3">Estado</th>
                        <th class="px-5 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($customers as $customer)
                        <tr>
                            <td class="px-5 py-4">
                                <a href="{{ route('clientes.show', $customer) }}" class="font-semibold text-brand-700 hover:text-brand-500">{{ $customer->name }}</a>
                                <p class="mt-1 text-xs text-stone-500">{{ $customer->email ?? 'Sin correo registrado' }}</p>
                            </td>
                            <td class="px-5 py-4">{{ $customer->nit ?? '—' }}</td>
                            <td class="px-5 py-4">{{ $customer->phone ?? '—' }}</td>
                            <td class="px-5 py-4">{{ $customer->addresses_count }}</td>
                            <td class="px-5 py-4">{{ $customer->quotes_count }}</td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset ring-current/10 {{ $customer->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-stone-100 text-stone-600' }}">
                                    {{ $customer->is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('clientes.show', $customer) }}" class="font-semibold text-brand-700 hover:text-brand-500">Ver</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-10 text-center text-stone-500">Todavía no hay clientes registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($customers->hasPages())
            <div class="border-t border-stone-200 px-5 py-4">{{ $customers->links() }}</div>
        @endif
    </div>
@endsection
