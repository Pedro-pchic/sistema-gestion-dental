<?php

namespace App\Http\Requests;

use App\Models\Employee;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
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
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        $employee = $this->route('employee');
        $currentPositionId = $employee instanceof Employee ? $employee->position_id : null;

        return [
            'user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
                Rule::unique('employees', 'user_id')->ignore($this->route('employee')),
            ],
            'position_id' => [
                'nullable',
                'integer',
                Rule::exists('positions', 'id')->where(function (Builder $query) use ($currentPositionId): void {
                    $query->where('is_active', true);

                    if ($currentPositionId !== null) {
                        $query->orWhere('id', $currentPositionId);
                    }
                }),
            ],
            'nombres' => ['required', 'string', 'max:255'],
            'apellidos' => ['required', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:40'],
            'direccion' => ['nullable', 'string', 'max:1000'],
            'puesto' => ['nullable', 'string', 'max:255'],
            'fecha_contratacion' => ['nullable', 'date'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
