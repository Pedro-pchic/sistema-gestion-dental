@extends('layouts.admin')

@section('title', 'Nuevo gasto')
@section('heading', 'Nuevo gasto')
@section('subheading', 'Registra un gasto operativo o relaciona explícitamente una compra recibida')

@section('content')
    <form method="POST" action="{{ route('finanzas.gastos.store') }}" class="max-w-3xl rounded-xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
        @csrf
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            Recibir una compra no significa que esté pagada. Seleccionarla aquí declara explícitamente el gasto y usa su total guardado en el servidor.
        </div>
        <div class="mt-5 grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="purchase_id" class="block text-sm font-medium">Compra relacionada (opcional)</label>
                <select id="purchase_id" name="purchase_id" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
                    <option value="">Gasto manual</option>
                    @foreach ($purchases as $purchase)
                        <option value="{{ $purchase->id }}" @selected((string) old('purchase_id') === (string) $purchase->id)>{{ $purchase->number }} · {{ $purchase->supplier->name }} · Q {{ number_format((float) $purchase->total, 2) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="date" class="block text-sm font-medium">Fecha</label>
                <input id="date" name="date" type="date" value="{{ old('date', today()->toDateString()) }}" required class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
            </div>
            <div>
                <label for="amount" class="block text-sm font-medium">Monto manual</label>
                <input id="amount" name="amount" type="number" min="0.01" step="0.01" value="{{ old('amount') }}" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
                <p class="mt-1 text-xs text-stone-500">Se ignora cuando seleccionas una compra.</p>
            </div>
            <div>
                <label for="category" class="block text-sm font-medium">Categoría manual</label>
                <select id="category" name="category" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
                    <option value="">Selecciona una categoría</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->value }}" @selected(old('category') === $category->value)>{{ $category->label() }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-stone-500">Las compras usan automáticamente la categoría Compras.</p>
            </div>
            <div class="sm:col-span-2">
                <label for="description" class="block text-sm font-medium">Descripción</label>
                <textarea id="description" name="description" rows="4" maxlength="2000" required class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">{{ old('description') }}</textarea>
            </div>
        </div>
        <div class="mt-6 flex gap-3 border-t border-stone-200 pt-5">
            <button type="submit" class="rounded-lg bg-brand-700 px-4 py-2 font-semibold text-white hover:bg-brand-600">Guardar gasto</button>
            <a href="{{ route('finanzas.gastos.index') }}" class="rounded-lg border border-stone-300 px-4 py-2 font-semibold hover:bg-stone-50">Cancelar</a>
        </div>
    </form>
@endsection
