<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerInquiryRequest extends FormRequest
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
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'in:Productos,Pedido,Cotización,Otro'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Escribe tu nombre.',
            'name.max' => 'El nombre no puede exceder 255 caracteres.',
            'email.required' => 'Escribe tu correo electrónico.',
            'email.email' => 'Escribe un correo electrónico válido.',
            'email.max' => 'El correo no puede exceder 255 caracteres.',
            'subject.required' => 'Selecciona un asunto.',
            'subject.in' => 'Selecciona un asunto válido.',
            'message.required' => 'Escribe tu consulta.',
            'message.min' => 'La consulta debe contener al menos 10 caracteres.',
            'message.max' => 'La consulta no puede exceder 5000 caracteres.',
        ];
    }
}
