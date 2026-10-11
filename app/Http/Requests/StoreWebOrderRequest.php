<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreWebOrderRequest extends FormRequest
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
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_nit' => ['nullable', 'string', 'max:50'],
            'customer_phone' => ['nullable', 'string', 'max:40'],
            'customer_email' => ['required', 'email', 'max:255'],
            'address_label' => ['nullable', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:5000'],
            'city' => ['required', 'string', 'max:255'],
            'department' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer_name.required' => 'El nombre del cliente es obligatorio.',
            'customer_name.max' => 'El nombre no puede exceder 255 caracteres.',
            'customer_nit.max' => 'El NIT no puede exceder 50 caracteres.',
            'customer_phone.max' => 'El teléfono no puede exceder 40 caracteres.',
            'customer_email.required' => 'El correo electrónico es obligatorio.',
            'customer_email.email' => 'Ingresa un correo electrónico válido.',
            'customer_email.max' => 'El correo electrónico no puede exceder 255 caracteres.',
            'address.required' => 'La dirección es obligatoria.',
            'address.max' => 'La dirección no puede exceder 5000 caracteres.',
            'address_label.max' => 'La etiqueta no puede exceder 100 caracteres.',
            'city.required' => 'El municipio es obligatorio.',
            'city.max' => 'El municipio no puede exceder 255 caracteres.',
            'department.required' => 'El departamento es obligatorio.',
            'department.max' => 'El departamento no puede exceder 255 caracteres.',
            'notes.max' => 'Las notas no pueden exceder 5000 caracteres.',
        ];
    }
}
