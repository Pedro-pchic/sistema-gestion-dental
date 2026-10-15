<?php

namespace App\Http\Requests;

use App\Models\Treatment;
use Illuminate\Foundation\Http\FormRequest;

class StoreTreatmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Treatment::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160', 'unique:treatments,name'],
            'description' => ['nullable', 'string', 'max:10000'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'estimated_duration_minutes' => ['required', 'integer', 'min:1', 'max:32767'],
            'is_active' => ['sometimes', 'boolean'],
            'is_public' => ['sometimes', 'boolean'],
            'show_public_price' => ['sometimes', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del tratamiento es obligatorio.',
            'name.unique' => 'Ya existe un tratamiento con ese nombre.',
            'price.required' => 'Indica el precio del tratamiento.',
            'price.numeric' => 'El precio debe ser un importe válido.',
            'price.min' => 'El precio no puede ser negativo.',
            'estimated_duration_minutes.required' => 'Indica la duración estimada en minutos.',
            'estimated_duration_minutes.min' => 'La duración debe ser de al menos un minuto.',
        ];
    }
}
