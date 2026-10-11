<?php

namespace App\Http\Requests;

use App\Enums\FinancialCategory;
use App\Enums\PurchaseStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
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
            'purchase_id' => [
                'nullable',
                'integer',
                Rule::exists('purchases', 'id')->where('status', PurchaseStatus::Received->value),
                Rule::unique('expenses', 'purchase_id'),
            ],
            'date' => ['required', 'date_format:Y-m-d'],
            'amount' => [
                'nullable',
                'required_without:purchase_id',
                'numeric',
                'decimal:0,2',
                'gt:0',
                'max:999999999999.99',
            ],
            'category' => [
                'nullable',
                'required_without:purchase_id',
                Rule::enum(FinancialCategory::class)->only(FinancialCategory::manualExpenseCases()),
            ],
            'description' => ['required', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'purchase_id.exists' => 'Selecciona una compra recibida válida.',
            'purchase_id.unique' => 'Esta compra ya tiene un gasto relacionado.',
            'date.required' => 'La fecha del gasto es obligatoria.',
            'date.date_format' => 'La fecha del gasto no es válida.',
            'amount.required_without' => 'El monto del gasto manual es obligatorio.',
            'amount.gt' => 'El monto del gasto debe ser mayor que cero.',
            'category.required_without' => 'La categoría del gasto manual es obligatoria.',
            'category.enum' => 'Selecciona una categoría de gasto válida.',
            'description.required' => 'La descripción del gasto es obligatoria.',
            'description.max' => 'La descripción no puede exceder 2000 caracteres.',
        ];
    }
}
