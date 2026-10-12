<?php

namespace App\Http\Requests;

use App\Enums\TreatmentPlanStatus;
use App\Models\TreatmentPlan;
use App\Models\TreatmentPlanItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateTreatmentPlanItemRequest extends FormRequest
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
            'status' => ['required', Rule::in([
                TreatmentPlanStatus::Pending->value,
                TreatmentPlanStatus::InProgress->value,
                TreatmentPlanStatus::Cancelled->value,
            ])],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $item = $this->route('treatmentPlanItem');
            $plan = $this->route('treatmentPlan');
            $status = TreatmentPlanStatus::tryFrom((string) $this->input('status'));

            if (
                ! $item instanceof TreatmentPlanItem
                || ! $plan instanceof TreatmentPlan
                || in_array($plan->status, [TreatmentPlanStatus::Completed, TreatmentPlanStatus::Cancelled], true)
                || $status === null
                || ! $item->status->canTransitionTo($status)
            ) {
                $validator->errors()->add('status', 'La partida no puede cambiar a ese estado.');
            }
        }];
    }
}
