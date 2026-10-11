<?php

namespace App\Http\Requests;

use App\Models\Patient;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePatientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $patient = $this->route('patient');

        return $patient instanceof Patient
            && ($this->user()?->can('update', $patient) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:2000'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ];

        if ($this->user()?->can('manage-patient-assignments')) {
            $rules['dentist_ids_present'] = ['sometimes', 'accepted'];
            $rules['dentist_ids'] = ['sometimes', 'array'];
            $rules['dentist_ids.*'] = [
                'integer',
                Rule::exists('dentists', 'id')
                    ->where(fn (Builder $query): Builder => $query->where('is_active', true)),
            ];
        } else {
            $rules['dentist_ids_present'] = ['prohibited'];
            $rules['dentist_ids'] = ['prohibited'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'Los nombres son obligatorios.',
            'first_name.max' => 'Los nombres no pueden exceder 120 caracteres.',
            'last_name.required' => 'Los apellidos son obligatorios.',
            'last_name.max' => 'Los apellidos no pueden exceder 120 caracteres.',
            'date_of_birth.date' => 'Ingresa una fecha de nacimiento válida.',
            'date_of_birth.before_or_equal' => 'La fecha de nacimiento no puede ser futura.',
            'phone.max' => 'El teléfono no puede exceder 40 caracteres.',
            'address.max' => 'La dirección no puede exceder 2000 caracteres.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'email.max' => 'El correo no puede exceder 255 caracteres.',
            'is_active.required' => 'El estado del paciente es obligatorio.',
            'is_active.boolean' => 'El estado del paciente no es válido.',
            'dentist_ids_present.accepted' => 'La solicitud de asignación no es válida.',
            'dentist_ids.prohibited' => 'No tienes permiso para asignar odontólogos.',
            'dentist_ids.array' => 'La selección de odontólogos no es válida.',
            'dentist_ids.*.exists' => 'Selecciona odontólogos activos.',
        ];
    }
}
