<?php

namespace App\Http\Requests;

use App\Enums\InventoryMovementType;
use App\Models\Inventory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryMovementRequest extends FormRequest
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
        $minimumQuantity = $this->input('type') === InventoryMovementType::Adjustment->value ? '0' : '1';

        return [
            'product_id' => ['required', 'integer', Rule::exists('inventories', 'product_id')],
            'type' => ['required', Rule::enum(InventoryMovementType::class)],
            'quantity' => ['required', 'integer', "min:$minimumQuantity", 'max:'.Inventory::MAX_STOCK],
            'reason' => ['nullable', 'string', 'max:500', 'required_if:type,adjustment'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'product_id.required' => 'Selecciona un producto.',
            'product_id.exists' => 'El producto seleccionado no tiene un inventario válido.',
            'type.required' => 'Selecciona el tipo de movimiento.',
            'type.enum' => 'El tipo de movimiento no es válido.',
            'quantity.required' => 'Ingresa una cantidad o existencia resultante.',
            'quantity.integer' => 'La cantidad debe ser un número entero.',
            'quantity.min' => 'Las entradas y salidas deben ser mayores que cero; un ajuste no puede ser negativo.',
            'quantity.max' => 'La existencia resultante excede el valor máximo permitido.',
            'reason.required_if' => 'El motivo es obligatorio para realizar un ajuste.',
            'reason.max' => 'El motivo no puede exceder 500 caracteres.',
        ];
    }
}
