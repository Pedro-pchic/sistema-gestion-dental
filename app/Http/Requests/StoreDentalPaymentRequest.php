<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Models\DentalInvoice;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDentalPaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $invoice = $this->route('dentalInvoice');

        return $invoice instanceof DentalInvoice
            && ($this->user()?->can('recordPayment', $invoice) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:9999999999.99'],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'payment_date' => ['required', 'date', 'before_or_equal:today'],
            'idempotency_key' => ['required', 'uuid'],
            'reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'amount.decimal' => 'El monto debe tener como máximo dos decimales.',
            'amount.gt' => 'El monto debe ser mayor que cero.',
            'payment_date.before_or_equal' => 'La fecha del pago no puede estar en el futuro.',
            'idempotency_key.uuid' => 'No se pudo validar la solicitud de pago. Recarga la factura.',
        ];
    }
}
