<?php

namespace App\Http\Requests;

use App\Models\Appointment;
use App\Models\Dentist;
use App\Models\Patient;
use App\Models\Role;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Appointment::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'patient_id' => [
                'required',
                'integer',
                Rule::exists('patients', 'id')->where(fn (Builder $query): Builder => $query
                    ->where('is_active', true)
                    ->whereNull('deleted_at')),
            ],
            'dentist_id' => [
                'required',
                'integer',
                Rule::exists('dentists', 'id')->where('is_active', true),
            ],
            'starts_at' => ['required', 'date_format:Y-m-d\\TH:i'],
            'ends_at' => ['required', 'date_format:Y-m-d\\TH:i', 'after:starts_at'],
            'planned_treatment' => ['required', 'string', 'max:255'],
            'administrative_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['patient_id', 'dentist_id', 'starts_at', 'ends_at'])) {
                return;
            }

            $timezone = config('appointments.timezone');
            $startsAt = CarbonImmutable::createFromFormat('!Y-m-d\\TH:i', $this->string('starts_at')->toString(), $timezone);

            if ($startsAt !== false && $startsAt->lessThanOrEqualTo(now($timezone))) {
                $validator->errors()->add('starts_at', 'La cita debe programarse en una fecha y hora futuras.');
            }

            $patient = Patient::query()->find($this->integer('patient_id'));
            $dentist = Dentist::query()
                ->with('user:id,is_active,role_id')
                ->whereKey($this->integer('dentist_id'))
                ->where('is_active', true)
                ->first();

            if (
                $dentist === null
                || ! $dentist->user?->is_active
                || ! $dentist->user?->hasRole(Role::DENTIST)
            ) {
                $validator->errors()->add('dentist_id', 'Selecciona un odontólogo activo.');

                return;
            }

            if ($patient !== null && ! $patient->dentists()->where('dentists.id', $dentist->id)->exists()) {
                $validator->errors()->add('dentist_id', 'El odontólogo debe estar asignado previamente a este paciente.');
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'patient_id.required' => 'Selecciona un paciente activo.',
            'patient_id.exists' => 'Selecciona un paciente activo.',
            'dentist_id.required' => 'Selecciona un odontólogo activo.',
            'dentist_id.exists' => 'Selecciona un odontólogo activo.',
            'starts_at.required' => 'Indica la fecha y hora de inicio.',
            'starts_at.date_format' => 'Indica una fecha y hora de inicio válida.',
            'ends_at.required' => 'Indica la fecha y hora de finalización.',
            'ends_at.date_format' => 'Indica una fecha y hora de finalización válida.',
            'ends_at.after' => 'La hora de finalización debe ser posterior al inicio.',
            'planned_treatment.required' => 'Describe el tratamiento previsto.',
            'planned_treatment.max' => 'La descripción no puede exceder 255 caracteres.',
            'administrative_notes.max' => 'Las observaciones no pueden exceder 2000 caracteres.',
        ];
    }
}
