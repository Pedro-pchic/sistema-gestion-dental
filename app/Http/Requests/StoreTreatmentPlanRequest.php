<?php

namespace App\Http\Requests;

use App\Models\Patient;
use App\Models\TreatmentPlan;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTreatmentPlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $patient = $this->route('patient');

        return $patient instanceof Patient
            && ($this->user()?->can('create', [TreatmentPlan::class, $patient]) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:10000'],
            'items' => ['required', 'array', 'min:1', 'max:20'],
            'items.*' => ['required', 'array:treatment_id,quantity,estimated_unit_price'],
            'items.*.treatment_id' => [
                'required',
                'integer',
                Rule::exists('treatments', 'id')->where(fn (Builder $query): Builder => $query->where('is_active', true)),
            ],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:32767'],
            'items.*.estimated_unit_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'title.required' => 'Indica el nombre del plan de tratamiento.',
            'items.required' => 'Agrega al menos un tratamiento al plan.',
            'items.min' => 'Agrega al menos un tratamiento al plan.',
            'items.*.treatment_id.required' => 'Selecciona el tratamiento de cada partida.',
            'items.*.treatment_id.exists' => 'Cada tratamiento seleccionado debe estar activo.',
            'items.*.quantity.min' => 'La cantidad debe ser de al menos uno.',
            'items.*.estimated_unit_price.min' => 'El precio estimado no puede ser negativo.',
        ];
    }
}
