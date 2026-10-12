<?php

namespace App\Http\Requests;

use App\Models\PatientTreatment;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePatientTreatmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $patientTreatment = $this->route('patientTreatment');

        return $patientTreatment instanceof PatientTreatment
            && ($this->user()?->can('update', $patientTreatment) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'procedure_description' => ['required', 'string', 'max:10000'],
            'clinical_notes' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
