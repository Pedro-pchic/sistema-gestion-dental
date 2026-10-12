<?php

namespace App\Http\Requests;

use App\Enums\TreatmentPlanStatus;
use App\Models\TreatmentPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateTreatmentPlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $treatmentPlan = $this->route('treatmentPlan');

        return $treatmentPlan instanceof TreatmentPlan
            && ($this->user()?->can('update', $treatmentPlan) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(array_map(
                fn (TreatmentPlanStatus $status): string => $status->value,
                TreatmentPlanStatus::cases(),
            ))],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $treatmentPlan = $this->route('treatmentPlan');
            $status = TreatmentPlanStatus::tryFrom((string) $this->input('status'));

            if ($status === null || ! $treatmentPlan->status->canTransitionTo($status)) {
                $validator->errors()->add('status', 'El plan no puede cambiar a ese estado.');

                return;
            }

            if (
                $status === TreatmentPlanStatus::Completed
                && $treatmentPlan->items()->where('status', '!=', TreatmentPlanStatus::Completed->value)->exists()
            ) {
                $validator->errors()->add('status', 'Completa los procedimientos asociados antes de cerrar el plan.');
            }
        }];
    }
}
