<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class StoreProductRequest extends ProductImageRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'sku' => ['required', 'string', 'max:255', Rule::unique('products', 'sku')],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
            'is_active' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_id.required' => 'La categoría es obligatoria.',
            'category_id.exists' => 'La categoría seleccionada no es válida.',
            'sku.required' => 'El SKU es obligatorio.',
            'sku.unique' => 'Ya existe un producto con este SKU.',
            'name.required' => 'El nombre del producto es obligatorio.',
            'description.max' => 'La descripción no puede exceder 5000 caracteres.',
            'price.required' => 'El precio es obligatorio.',
            'price.numeric' => 'El precio debe ser un número.',
            'price.decimal' => 'El precio puede tener como máximo dos decimales.',
            'price.min' => 'El precio no puede ser negativo.',
            'price.max' => 'El precio excede el valor máximo permitido.',
            'image.image' => 'El archivo debe ser una imagen válida.',
            'image.mimes' => 'La fotografía debe estar en formato JPG, JPEG, PNG o WebP.',
            'image.max' => 'La fotografía no puede superar 2 MB.',
        ];
    }
}
