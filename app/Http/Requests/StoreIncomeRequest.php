<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreIncomeRequest extends FormRequest
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
            'date' => ['required', 'date_format:Y-m-d'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:999999999999.99'],
            'description' => ['required', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'date.required' => 'La fecha del ingreso es obligatoria.',
            'date.date_format' => 'La fecha del ingreso no es válida.',
            'amount.required' => 'El monto del ingreso es obligatorio.',
            'amount.gt' => 'El monto del ingreso debe ser mayor que cero.',
            'description.required' => 'La justificación del ingreso manual es obligatoria.',
            'description.max' => 'La descripción no puede exceder 2000 caracteres.',
        ];
    }
}
