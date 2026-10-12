<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTreatmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('treatment')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:160',
                Rule::unique('treatments', 'name')->ignore($this->route('treatment')),
            ],
            'description' => ['nullable', 'string', 'max:10000'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'estimated_duration_minutes' => ['required', 'integer', 'min:1', 'max:32767'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del tratamiento es obligatorio.',
            'name.unique' => 'Ya existe un tratamiento con ese nombre.',
            'price.numeric' => 'El precio debe ser un importe válido.',
            'price.min' => 'El precio no puede ser negativo.',
            'estimated_duration_minutes.min' => 'La duración debe ser de al menos un minuto.',
        ];
    }
}
