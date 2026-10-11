@extends('layouts.admin')

@section('title', 'Nuevo ingreso manual')
@section('heading', 'Nuevo ingreso manual')
@section('subheading', 'Registra únicamente ingresos que no provienen de pagos de facturas')

@section('content')
    <form method="POST" action="{{ route('finanzas.ingresos.store') }}" class="max-w-2xl rounded-xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
        @csrf
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="date" class="block text-sm font-medium">Fecha</label>
                <input id="date" name="date" type="date" value="{{ old('date', today()->toDateString()) }}" required class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
            </div>
            <div>
                <label for="amount" class="block text-sm font-medium">Monto</label>
                <input id="amount" name="amount" type="number" min="0.01" step="0.01" value="{{ old('amount') }}" required class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
            </div>
            <div class="sm:col-span-2">
                <label for="description" class="block text-sm font-medium">Justificación</label>
                <textarea id="description" name="description" rows="4" maxlength="2000" required class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">{{ old('description') }}</textarea>
            </div>
        </div>
        <div class="mt-6 flex gap-3 border-t border-stone-200 pt-5">
            <button type="submit" class="rounded-lg bg-brand-700 px-4 py-2 font-semibold text-white hover:bg-brand-600">Guardar ingreso</button>
            <a href="{{ route('finanzas.ingresos.index') }}" class="rounded-lg border border-stone-300 px-4 py-2 font-semibold hover:bg-stone-50">Cancelar</a>
        </div>
    </form>
@endsection
