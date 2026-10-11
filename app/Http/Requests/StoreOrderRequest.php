<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
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
            'customer_id' => [
                'required',
                'integer',
                Rule::exists('customers', 'id')->where('is_active', true),
            ],
            'order_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'details' => ['required', 'array', 'min:1'],
            'details.*' => ['required', 'array:product_id,quantity,unit_price'],
            'details.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'details.*.quantity' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'details.*.unit_price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer_id.required' => 'Selecciona un cliente activo.',
            'customer_id.exists' => 'El cliente seleccionado no existe o está inactivo.',
            'order_date.required' => 'La fecha del pedido es obligatoria.',
            'order_date.date' => 'La fecha del pedido no es válida.',
            'notes.max' => 'Las notas no pueden exceder 5000 caracteres.',
            'details.required' => 'Agrega al menos un producto al pedido.',
            'details.min' => 'Agrega al menos un producto al pedido.',
            'details.*.product_id.required' => 'Selecciona un producto para cada detalle.',
            'details.*.product_id.exists' => 'Uno de los productos seleccionados no existe.',
            'details.*.quantity.required' => 'La cantidad es obligatoria para cada producto.',
            'details.*.quantity.integer' => 'La cantidad debe ser un número entero.',
            'details.*.quantity.min' => 'La cantidad debe ser mayor que cero.',
            'details.*.unit_price.required' => 'El precio unitario es obligatorio para cada producto.',
            'details.*.unit_price.numeric' => 'El precio unitario debe ser un número.',
            'details.*.unit_price.decimal' => 'El precio unitario puede tener como máximo dos decimales.',
            'details.*.unit_price.min' => 'El precio unitario no puede ser negativo.',
            'details.*.unit_price.max' => 'El precio unitario excede el valor máximo permitido.',
        ];
    }
}
