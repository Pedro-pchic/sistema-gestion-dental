@php
    $formDetails = old('details', $details);
@endphp

<form data-order-form method="POST" action="{{ isset($order) ? route('pedidos.update', $order) : route('pedidos.store') }}" class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
    @csrf
    @if (isset($order))
        @method('PUT')
    @endif

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="customer_id" class="block text-sm font-medium">Cliente</label>
            <select id="customer_id" name="customer_id" required class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
                <option value="">Selecciona un cliente activo</option>
                @foreach ($customers as $customerOption)
                    <option value="{{ $customerOption->id }}" @selected((string) old('customer_id', $order->customer_id ?? '') === (string) $customerOption->id)>
                        {{ $customerOption->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="order_date" class="block text-sm font-medium">Fecha del pedido</label>
            <input id="order_date" name="order_date" type="date" value="{{ old('order_date', isset($order) ? $order->order_date->format('Y-m-d') : today()->toDateString()) }}" required
                class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
        </div>

        <div class="sm:col-span-2">
            <label for="notes" class="block text-sm font-medium">Notas</label>
            <textarea id="notes" name="notes" rows="3" maxlength="5000" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">{{ old('notes', $order->notes ?? '') }}</textarea>
        </div>
    </div>

    <section class="mt-8">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold">Productos</h2>
                <p class="text-sm text-stone-600">Crear o editar un pedido no descuenta existencias; el total se calcula en el servidor.</p>
            </div>
            <button type="button" data-add-order-line class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold hover:bg-stone-50">Agregar producto</button>
        </div>

        <div data-order-lines class="mt-4 flex flex-col gap-3">
            @foreach ($formDetails as $index => $detail)
                @include('orders._detail-row', ['products' => $products, 'detail' => $detail, 'index' => $index])
            @endforeach
        </div>

        <template data-order-line-template>
            @include('orders._detail-row', ['products' => $products, 'detail' => [], 'index' => '__INDEX__'])
        </template>

        <div class="mt-5 flex justify-end border-t border-stone-200 pt-4">
            <p class="text-lg font-bold">Total: <span data-order-total class="tabular-nums">Q 0.00</span></p>
        </div>
    </section>

    <div class="mt-6 flex flex-wrap gap-3 border-t border-stone-200 pt-5">
        <button type="submit" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600">{{ isset($order) ? 'Guardar pedido' : 'Crear pedido pendiente' }}</button>
        <a href="{{ isset($order) ? route('pedidos.show', $order) : route('pedidos.index') }}" class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold hover:bg-stone-50">Cancelar</a>
    </div>
</form>
