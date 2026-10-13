@extends('layouts.admin')

@section('title', 'Crear plan de tratamiento')
@section('heading', 'Crear plan de tratamiento')
@section('subheading', $patient->first_name.' '.$patient->last_name.' · Ficha '.$patient->record_number)

@section('content')
    @if ($treatments->isEmpty())
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-950">
            Agrega un tratamiento activo al catálogo antes de crear un plan.
            <a href="{{ route('tratamientos.index') }}" class="ml-1 font-semibold underline">Ver catálogo</a>
        </div>
    @else
        <form method="POST" action="{{ route('pacientes.planes.store', $patient) }}" class="ui-card p-5 sm:p-6">
            @csrf
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="title" class="block text-sm font-medium">Nombre del plan</label>
                    <input id="title" name="title" type="text" maxlength="160" value="{{ old('title') }}" required class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
                </div>
                <div class="sm:col-span-2">
                    <label for="description" class="block text-sm font-medium">Descripción y objetivo clínico</label>
                    <textarea id="description" name="description" rows="3" maxlength="10000" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-brand-500 focus:ring-2 focus:ring-brand-200">{{ old('description') }}</textarea>
                </div>
            </div>

            <div class="mt-7 border-t border-stone-200 pt-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div><h2 class="font-semibold">Tratamientos previstos</h2><p class="mt-1 text-sm text-stone-500">El precio vacío toma el valor vigente del catálogo al crear el plan.</p></div>
                    <button type="button" data-add-plan-item class="ui-button ui-button-secondary">Agregar tratamiento</button>
                </div>
                <div class="mt-4 flex flex-col gap-4" data-plan-items data-next-index="1">
                    <div class="grid gap-4 rounded-lg border border-stone-200 p-4 sm:grid-cols-[2fr_8rem_12rem]">
                        <div>
                            <label for="item_treatment_0" class="block text-sm font-medium">Tratamiento</label>
                            <select id="item_treatment_0" name="items[0][treatment_id]" required class="mt-2 w-full rounded-lg border border-stone-300 bg-white px-3 py-2">
                                <option value="">Selecciona un tratamiento</option>
                                @foreach ($treatments as $treatment)
                                    <option value="{{ $treatment->id }}" @selected((string) old('items.0.treatment_id') === (string) $treatment->id)>{{ $treatment->name }} — Q {{ number_format((float) $treatment->price, 2) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div><label for="item_quantity_0" class="block text-sm font-medium">Cantidad</label><input id="item_quantity_0" name="items[0][quantity]" type="number" min="1" max="32767" value="{{ old('items.0.quantity', 1) }}" required class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2"></div>
                        <div><label for="item_price_0" class="block text-sm font-medium">Precio estimado por unidad (Q)</label><input id="item_price_0" name="items[0][estimated_unit_price]" type="number" min="0" max="99999999.99" step="0.01" value="{{ old('items.0.estimated_unit_price') }}" placeholder="Precio del catálogo" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2"></div>
                    </div>
                    @foreach (old('items', []) as $itemIndex => $item)
                        @if ($itemIndex > 0)
                            <div class="grid gap-4 rounded-lg border border-stone-200 p-4 sm:grid-cols-[2fr_8rem_12rem]">
                                <div><label for="item_treatment_{{ $itemIndex }}" class="block text-sm font-medium">Tratamiento</label><select id="item_treatment_{{ $itemIndex }}" name="items[{{ $itemIndex }}][treatment_id]" required class="mt-2 w-full rounded-lg border border-stone-300 bg-white px-3 py-2"><option value="">Selecciona un tratamiento</option>@foreach ($treatments as $treatment)<option value="{{ $treatment->id }}" @selected((string) ($item['treatment_id'] ?? '') === (string) $treatment->id)>{{ $treatment->name }} — Q {{ number_format((float) $treatment->price, 2) }}</option>@endforeach</select></div>
                                <div><label for="item_quantity_{{ $itemIndex }}" class="block text-sm font-medium">Cantidad</label><input id="item_quantity_{{ $itemIndex }}" name="items[{{ $itemIndex }}][quantity]" type="number" min="1" max="32767" value="{{ $item['quantity'] ?? 1 }}" required class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2"></div>
                                <div><label for="item_price_{{ $itemIndex }}" class="block text-sm font-medium">Precio estimado por unidad (Q)</label><input id="item_price_{{ $itemIndex }}" name="items[{{ $itemIndex }}][estimated_unit_price]" type="number" min="0" max="99999999.99" step="0.01" value="{{ $item['estimated_unit_price'] ?? '' }}" placeholder="Precio del catálogo" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2"></div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3 border-t border-stone-200 pt-5">
                <a href="{{ route('pacientes.planes.index', $patient) }}" class="ui-button ui-button-secondary">Cancelar</a>
                <button type="submit" class="ui-button ui-button-primary">Guardar plan</button>
            </div>
        </form>
        <template data-plan-item-template>
            <div class="grid gap-4 rounded-lg border border-stone-200 p-4 sm:grid-cols-[2fr_8rem_12rem]">
                <div><label for="item_treatment___INDEX__" class="block text-sm font-medium">Tratamiento</label><select id="item_treatment___INDEX__" name="items[__INDEX__][treatment_id]" required class="mt-2 w-full rounded-lg border border-stone-300 bg-white px-3 py-2"><option value="">Selecciona un tratamiento</option>@foreach ($treatments as $treatment)<option value="{{ $treatment->id }}">{{ $treatment->name }} — Q {{ number_format((float) $treatment->price, 2) }}</option>@endforeach</select></div>
                <div><label for="item_quantity___INDEX__" class="block text-sm font-medium">Cantidad</label><input id="item_quantity___INDEX__" name="items[__INDEX__][quantity]" type="number" min="1" max="32767" value="1" required class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2"></div>
                <div><label for="item_price___INDEX__" class="block text-sm font-medium">Precio estimado por unidad (Q)</label><input id="item_price___INDEX__" name="items[__INDEX__][estimated_unit_price]" type="number" min="0" max="99999999.99" step="0.01" placeholder="Precio del catálogo" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2"></div>
            </div>
        </template>
        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const container = document.querySelector('[data-plan-items]');
                    const template = document.querySelector('[data-plan-item-template]');
                    const button = document.querySelector('[data-add-plan-item]');

                    button.addEventListener('click', () => {
                        const index = Number(container.dataset.nextIndex);

                        if (index >= 20) {
                            return;
                        }

                        container.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', String(index)));
                        container.dataset.nextIndex = String(index + 1);
                    });
                });
            </script>
        @endpush
    @endif
@endsection
