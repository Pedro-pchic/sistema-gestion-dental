@extends('layouts.admin')

@section('title', 'Crear factura dental')
@section('heading', 'Crear factura dental')
@section('subheading', 'Solo se pueden agregar procedimientos que ya fueron realizados')

@section('content')
    <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
        <form method="GET" action="{{ route('facturacion.facturas.create') }}" class="flex flex-col gap-4 sm:flex-row sm:items-end">
            <label class="flex flex-1 flex-col gap-1 text-sm font-medium text-stone-700">Buscar paciente por nombre, teléfono o código
                <input type="search" name="search" value="{{ $search }}" class="rounded-md border border-stone-300 px-3 py-2 font-normal" required>
            </label>
            <button type="submit" class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold hover:bg-stone-50">Buscar</button>
        </form>

        @if ($patients->isNotEmpty())
            <form method="GET" action="{{ route('facturacion.facturas.create') }}" class="mt-5 flex flex-col gap-4 border-t border-stone-200 pt-5 sm:flex-row sm:items-end">
                <input type="hidden" name="search" value="{{ $search }}">
                <label class="flex flex-1 flex-col gap-1 text-sm font-medium text-stone-700">Paciente
                    <select name="patient_id" required class="rounded-md border border-stone-300 px-3 py-2 font-normal">
                        <option value="">Selecciona un paciente</option>
                        @foreach ($patients as $option)
                            <option value="{{ $option->id }}" @selected($patient?->is($option))>{{ $option->first_name }} {{ $option->last_name }} · {{ $option->record_number }}</option>
                        @endforeach
                    </select>
                </label>
                <button type="submit" class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold hover:bg-stone-50">Ver procedimientos</button>
            </form>
        @elseif ($search !== '')
            <p class="mt-5 border-t border-stone-200 pt-5 text-sm text-stone-600">No se encontraron pacientes activos con esos datos.</p>
        @else
            <p class="mt-5 border-t border-stone-200 pt-5 text-sm text-stone-600">Busca un paciente para ver sus procedimientos disponibles.</p>
        @endif
    </section>

    @if ($patient)
        <section class="mt-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
            <h2 class="text-lg font-semibold">Procedimientos de {{ $patient->first_name }} {{ $patient->last_name }}</h2>
            <p class="mt-1 text-sm text-stone-600">El precio se toma del registro histórico del procedimiento. Las notas clínicas no se incluyen.</p>

            @if ($procedures->isEmpty())
                <p class="mt-5 rounded-lg bg-stone-50 p-4 text-sm text-stone-600">No hay procedimientos realizados y pendientes de facturar.</p>
            @else
                <form method="POST" action="{{ route('facturacion.facturas.store') }}" class="mt-5 flex flex-col gap-5">
                    @csrf
                    <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                    <div class="overflow-x-auto rounded-lg ring-1 ring-stone-200">
                        <table class="min-w-full divide-y divide-stone-200 text-sm">
                            <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">
                                <tr><th class="px-4 py-3">Incluir</th><th class="px-4 py-3">Procedimiento</th><th class="px-4 py-3">Realizado</th><th class="px-4 py-3 text-right">Precio histórico</th></tr>
                            </thead>
                            <tbody class="divide-y divide-stone-100">
                                @foreach ($procedures as $procedure)
                                    <tr>
                                        <td class="px-4 py-3"><input type="checkbox" name="patient_treatment_ids[]" value="{{ $procedure->id }}" @checked(in_array((string) $procedure->id, old('patient_treatment_ids', []), true)) class="size-4 rounded border-stone-300 text-brand-700 focus:ring-brand-600"></td>
                                        <td class="px-4 py-3 font-medium">{{ $procedure->treatment->name }}</td>
                                        <td class="px-4 py-3">{{ $procedure->performed_at->format('d/m/Y H:i') }}</td>
                                        <td class="px-4 py-3 text-right tabular-nums">Q {{ number_format((float) $procedure->price_applied, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @error('patient_treatment_ids')<p class="text-sm text-red-700">{{ $message }}</p>@enderror
                    <button type="submit" class="self-start rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600">Crear borrador</button>
                </form>
            @endif
        </section>
    @endif

    <div class="mt-6"><a href="{{ route('facturacion.facturas.index') }}" class="rounded-lg border border-stone-300 bg-white px-4 py-2 text-sm font-semibold hover:bg-stone-50">Volver a facturas</a></div>
@endsection
