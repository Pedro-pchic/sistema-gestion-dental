@extends('layouts.store')

@section('title', 'Checkout')
@section('heading', 'Finalizar pedido')
@section('subheading', 'El precio y la disponibilidad se verificarán nuevamente al crear el pedido')

@section('content')
    <div class="grid gap-6 lg:grid-cols-[1fr_22rem] lg:items-start">
        <form method="POST" action="{{ route('checkout.store') }}" class="ui-card p-6">
            @csrf

            <fieldset>
                <legend class="text-lg font-bold">Datos del cliente</legend>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="customer_name" class="block text-sm font-medium">Nombre</label>
                        <input id="customer_name" name="customer_name" value="{{ old('customer_name') }}" required maxlength="255"
                            class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
                    </div>
                    <div>
                        <label for="customer_email" class="block text-sm font-medium">Correo electrónico</label>
                        <input id="customer_email" name="customer_email" type="email" value="{{ old('customer_email') }}" required maxlength="255"
                            class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
                    </div>
                    <div>
                        <label for="customer_phone" class="block text-sm font-medium">Teléfono</label>
                        <input id="customer_phone" name="customer_phone" value="{{ old('customer_phone') }}" maxlength="40"
                            class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
                    </div>
                    <div>
                        <label for="customer_nit" class="block text-sm font-medium">NIT</label>
                        <input id="customer_nit" name="customer_nit" value="{{ old('customer_nit') }}" maxlength="50"
                            class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
                    </div>
                </div>
            </fieldset>

            <fieldset class="mt-8 border-t border-stone-200 pt-6">
                <legend class="text-lg font-bold">Dirección de entrega</legend>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="address_label" class="block text-sm font-medium">Etiqueta</label>
                        <input id="address_label" name="address_label" value="{{ old('address_label') }}" maxlength="100" placeholder="Casa, oficina..."
                            class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="address" class="block text-sm font-medium">Dirección completa</label>
                        <textarea id="address" name="address" rows="3" required maxlength="5000"
                            class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">{{ old('address') }}</textarea>
                    </div>
                    <div>
                        <label for="city" class="block text-sm font-medium">Municipio</label>
                        <input id="city" name="city" value="{{ old('city') }}" required maxlength="255"
                            class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
                    </div>
                    <div>
                        <label for="department" class="block text-sm font-medium">Departamento</label>
                        <input id="department" name="department" value="{{ old('department') }}" required maxlength="255"
                            class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="notes" class="block text-sm font-medium">Notas</label>
                        <textarea id="notes" name="notes" rows="3" maxlength="5000"
                            class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </fieldset>

            <div class="mt-6 flex flex-wrap gap-3 border-t border-stone-200 pt-5">
                <button type="submit" @disabled(! $cart['can_checkout'])
                    class="ui-button ui-button-primary disabled:bg-stone-400">Crear pedido</button>
                <a href="{{ route('carrito.index') }}" class="ui-button ui-button-secondary">Volver al carrito</a>
            </div>
        </form>

        <aside class="ui-card p-5">
            <h2 class="text-lg font-bold">Resumen</h2>
            <ul class="mt-4 flex flex-col gap-4">
                @foreach ($cart['items'] as $item)
                    <li class="flex justify-between gap-4 border-b border-stone-100 pb-4 text-sm">
                        <div>
                            <p class="font-semibold">{{ $item['product']->name }}</p>
                            <p class="mt-1 text-stone-500">Cantidad: {{ $item['quantity'] }}</p>
                        </div>
                        <p class="font-semibold tabular-nums">Q {{ number_format((float) $item['subtotal'], 2) }}</p>
                    </li>
                @endforeach
            </ul>
            <div class="mt-4 flex justify-between text-lg font-bold">
                <span>Total</span>
                <span class="tabular-nums">Q {{ number_format((float) $cart['total'], 2) }}</span>
            </div>
        </aside>
    </div>
@endsection
