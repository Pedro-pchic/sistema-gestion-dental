<?php

namespace App\Http\Requests;

use App\Models\DentalInvoice;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDentalInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', DentalInvoice::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'patient_treatment_ids' => ['required', 'array', 'min:1', 'max:100'],
            'patient_treatment_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('patient_treatments', 'id')->where(function (Builder $query): void {
                    $query->where('patient_id', $this->input('patient_id'))
                        ->whereNull('deleted_at');
                }),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'patient_id.required' => 'Selecciona un paciente.',
            'patient_treatment_ids.required' => 'Selecciona al menos un procedimiento realizado.',
            'patient_treatment_ids.min' => 'Selecciona al menos un procedimiento realizado.',
            'patient_treatment_ids.*.exists' => 'Selecciona procedimientos realizados del paciente elegido.',
            'patient_treatment_ids.*.distinct' => 'No repitas un procedimiento en la factura.',
        ];
    }
}
