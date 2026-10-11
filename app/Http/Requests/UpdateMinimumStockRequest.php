<?php

namespace App\Http\Requests;

use App\Models\Inventory;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMinimumStockRequest extends FormRequest
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
            'minimum_stock' => ['required', 'integer', 'min:0', 'max:'.Inventory::MAX_STOCK],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'minimum_stock.required' => 'El stock mínimo es obligatorio.',
            'minimum_stock.integer' => 'El stock mínimo debe ser un número entero.',
            'minimum_stock.min' => 'El stock mínimo no puede ser negativo.',
            'minimum_stock.max' => 'El stock mínimo excede el valor máximo permitido.',
        ];
    }
}
