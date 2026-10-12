<div>
    <!-- Happiness is not something readymade. It comes from your own actions. - Dalai Lama -->
</div>
@extends('layouts.admin')

@section('title', 'Registrar procedimiento')
@section('heading', 'Registrar procedimiento')
@section('subheading', $patient->first_name.' '.$patient->last_name.' · Ficha '.$patient->record_number)

@section('content')
    <form method="POST" action="{{ route('pacientes.tratamientos.store', $patient) }}" class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
        @csrf
        @include('patient-treatments._form')
        <p class="mt-5 rounded-lg bg-brand-50 px-4 py-3 text-sm text-brand-900">El precio vigente del catálogo se guardará en este procedimiento como precio histórico.</p>
        <div class="mt-6 flex justify-end gap-3 border-t border-stone-200 pt-5">
            <a href="{{ route('pacientes.historial-clinico', $patient) }}" class="ui-button ui-button-secondary">Cancelar</a>
            <button type="submit" class="ui-button ui-button-primary">Guardar procedimiento</button>
        </div>
    </form>
    @if ($planItems->isNotEmpty())
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const treatmentSelect = document.querySelector('#treatment_id');
                const planItemSelect = document.querySelector('#treatment_plan_item_id');

                planItemSelect.addEventListener('change', () => {
                    const option = planItemSelect.selectedOptions[0];
                    const treatmentId = option?.dataset.treatmentId;

                    if (treatmentId) {
                        treatmentSelect.value = treatmentId;
                    }
                });
            });
        </script>
    @endif
@endsection
