<?php

namespace App\Http\Requests;

use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePositionRequest extends FormRequest
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
        $departmentId = $this->input('department_id');

        return [
            'department_id' => ['required', 'integer', Rule::exists('departments', 'id')],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('positions', 'name')
                    ->where(fn (Builder $query): Builder => $query->where('department_id', $departmentId))
                    ->ignore($this->route('position')),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'department_id.required' => 'Selecciona un departamento válido.',
            'department_id.exists' => 'El departamento seleccionado no existe.',
            'name.required' => 'El nombre del puesto es obligatorio.',
            'name.unique' => 'Ya existe un puesto con ese nombre en el departamento.',
            'description.max' => 'La descripción no puede exceder 5000 caracteres.',
        ];
    }
}
