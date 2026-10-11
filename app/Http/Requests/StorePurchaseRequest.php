<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => [
                'required',
                'integer',
                Rule::exists('suppliers', 'id')->where('is_active', true),
            ],
            'order_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'details' => ['required', 'array', 'min:1'],
            'details.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'details.*.quantity' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'details.*.unit_cost' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'supplier_id.required' => 'Selecciona un proveedor activo.',
            'supplier_id.exists' => 'El proveedor seleccionado no existe o está inactivo.',
            'order_date.required' => 'La fecha de orden es obligatoria.',
            'order_date.date' => 'La fecha de orden no es válida.',
            'notes.max' => 'Las notas no pueden exceder 5000 caracteres.',
            'details.required' => 'Agrega al menos un producto a la orden.',
            'details.array' => 'Los productos de la orden no son válidos.',
            'details.min' => 'Agrega al menos un producto a la orden.',
            'details.*.product_id.required' => 'Selecciona un producto para cada detalle.',
            'details.*.product_id.exists' => 'Uno de los productos seleccionados no existe.',
            'details.*.quantity.required' => 'La cantidad es obligatoria para cada producto.',
            'details.*.quantity.integer' => 'La cantidad debe ser un número entero.',
            'details.*.quantity.min' => 'La cantidad debe ser mayor que cero.',
            'details.*.quantity.max' => 'La cantidad excede el valor máximo permitido.',
            'details.*.unit_cost.required' => 'El costo unitario es obligatorio para cada producto.',
            'details.*.unit_cost.numeric' => 'El costo unitario debe ser un número.',
            'details.*.unit_cost.decimal' => 'El costo unitario puede tener como máximo dos decimales.',
            'details.*.unit_cost.min' => 'El costo unitario no puede ser negativo.',
            'details.*.unit_cost.max' => 'El costo unitario excede el valor máximo permitido.',
        ];
    }
}
