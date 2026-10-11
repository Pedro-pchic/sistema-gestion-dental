<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAddressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:5000'],
            'city' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'address.required' => 'La dirección es obligatoria.',
            'address.max' => 'La dirección no puede exceder 5000 caracteres.',
            'label.max' => 'La etiqueta no puede exceder 100 caracteres.',
            'city.max' => 'El municipio no puede exceder 255 caracteres.',
            'department.max' => 'El departamento no puede exceder 255 caracteres.',
        ];
    }
}
