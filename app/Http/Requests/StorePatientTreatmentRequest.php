<?php

namespace App\Http\Requests;

use App\Enums\AppointmentStatus;
use App\Enums\TreatmentPlanStatus;
use App\Models\Patient;
use App\Models\PatientTreatment;
use App\Models\TreatmentPlanItem;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePatientTreatmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $patient = $this->route('patient');

        return $patient instanceof Patient
            && ($this->user()?->can('createClinicalRecord', $patient) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $patient = $this->route('patient');
        $dentistId = $this->user()?->dentist?->id;

        return [
            'treatment_id' => [
                'required',
                'integer',
                Rule::exists('treatments', 'id')->where(fn (Builder $query): Builder => $query->where('is_active', true)),
            ],
            'appointment_id' => [
                Rule::requiredIf(fn (): bool => ! config('dental.allow_treatment_without_appointment')),
                'nullable',
                'integer',
                Rule::exists('appointments', 'id')->where(fn (Builder $query): Builder => $query
                    ->where('patient_id', $patient->id)
                    ->where('dentist_id', $dentistId)
                    ->whereIn('status', [AppointmentStatus::InTreatment->value, AppointmentStatus::Completed->value])),
            ],
            'treatment_plan_item_id' => [
                'nullable',
                'integer',
                Rule::exists('treatment_plan_items', 'id')->where(fn (Builder $query): Builder => $query
                    ->where('treatment_id', $this->integer('treatment_id'))),
            ],
            'performed_at' => ['required', 'date_format:Y-m-d\\TH:i'],
            'procedure_description' => ['required', 'string', 'max:10000'],
            'clinical_notes' => ['nullable', 'string', 'max:10000'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['treatment_id', 'appointment_id', 'treatment_plan_item_id', 'performed_at'])) {
                return;
            }

            $patient = $this->route('patient');
            $dentistId = $this->user()?->dentist?->id;
            $treatmentId = $this->integer('treatment_id');
            $timezone = config('appointments.timezone');
            $performedAt = CarbonImmutable::createFromFormat(
                '!Y-m-d\\TH:i',
                $this->string('performed_at')->toString(),
                $timezone,
            );

            if ($performedAt !== false && $performedAt->greaterThan(now($timezone))) {
                $validator->errors()->add('performed_at', 'El procedimiento no puede registrarse con una fecha y hora futura.');

                return;
            }

            if ($this->filled('appointment_id') && PatientTreatment::query()
                ->where('appointment_id', $this->integer('appointment_id'))
                ->where('treatment_id', $treatmentId)
                ->exists()) {
                $validator->errors()->add('treatment_id', 'Este tratamiento ya está registrado para la cita seleccionada.');
            }

            if ($this->filled('treatment_plan_item_id')) {
                $planItem = TreatmentPlanItem::query()
                    ->with('plan')
                    ->find($this->integer('treatment_plan_item_id'));

                if (
                    $planItem === null
                    || $planItem->plan->patient_id !== $patient->id
                    || ! in_array($planItem->plan->status, [TreatmentPlanStatus::Pending, TreatmentPlanStatus::InProgress], true)
                    || in_array($planItem->status, [TreatmentPlanStatus::Completed, TreatmentPlanStatus::Cancelled], true)
                    || $planItem->patientTreatments()->count() >= $planItem->quantity
                ) {
                    $validator->errors()->add('treatment_plan_item_id', 'Selecciona una partida con procedimientos pendientes del plan de este paciente.');
                }
            }

            if (! $this->filled('appointment_id') && config('dental.allow_treatment_without_appointment')) {
                if ($performedAt !== false && PatientTreatment::query()
                    ->where('patient_id', $patient->id)
                    ->where('dentist_id', $dentistId)
                    ->where('treatment_id', $treatmentId)
                    ->whereNull('appointment_id')
                    ->where('performed_at', $performedAt->utc())
                    ->exists()) {
                    $validator->errors()->add('treatment_id', 'Ya existe este procedimiento para el paciente, fecha y odontólogo seleccionados.');
                }
            }
        }];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'treatment_id.required' => 'Selecciona un tratamiento activo.',
            'treatment_id.exists' => 'Selecciona un tratamiento activo.',
            'appointment_id.required' => 'Selecciona una cita completada o en atención.',
            'appointment_id.exists' => 'La cita debe pertenecer al paciente y al odontólogo responsable.',
            'performed_at.required' => 'Indica la fecha y hora del procedimiento.',
            'performed_at.date_format' => 'Indica una fecha y hora válidas.',
            'procedure_description.required' => 'Describe el procedimiento realizado.',
        ];
    }
}
