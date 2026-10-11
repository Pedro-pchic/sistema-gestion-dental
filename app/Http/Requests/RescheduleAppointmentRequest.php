<?php

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class RescheduleAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reschedule', $this->route('appointment')) ?? false;
    }

    public function rules(): array
    {
        return [
            'starts_at' => ['required', 'date_format:Y-m-d\\TH:i'],
            'ends_at' => ['required', 'date_format:Y-m-d\\TH:i', 'after:starts_at'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['starts_at', 'ends_at'])) {
                return;
            }

            $timezone = config('appointments.timezone');
            $startsAt = CarbonImmutable::createFromFormat('!Y-m-d\\TH:i', $this->string('starts_at')->toString(), $timezone);

            if ($startsAt !== false && $startsAt->lessThanOrEqualTo(now($timezone))) {
                $validator->errors()->add('starts_at', 'La cita debe reprogramarse para una fecha y hora futuras.');
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'starts_at.required' => 'Indica la nueva fecha y hora de inicio.',
            'starts_at.date_format' => 'Indica una nueva fecha y hora de inicio válida.',
            'ends_at.required' => 'Indica la nueva fecha y hora de finalización.',
            'ends_at.date_format' => 'Indica una nueva fecha y hora de finalización válida.',
            'ends_at.after' => 'La hora de finalización debe ser posterior al inicio.',
        ];
    }
}
