<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Enums\TreatmentPlanStatus;
use App\Http\Requests\StorePatientTreatmentRequest;
use App\Http\Requests\UpdatePatientTreatmentRequest;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\PatientTreatment;
use App\Models\PatientTreatmentEvent;
use App\Models\Treatment;
use App\Models\TreatmentPlan;
use App\Models\TreatmentPlanItem;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PatientTreatmentController extends Controller
{
    public function create(Request $request, Patient $patient): View
    {
        Gate::authorize('createClinicalRecord', $patient);

        $dentist = $request->user()->dentist;
        $appointments = $patient->appointments()
            ->where('dentist_id', $dentist->id)
            ->whereIn('status', [AppointmentStatus::InTreatment->value, AppointmentStatus::Completed->value])
            ->orderByDesc('starts_at')
            ->get(['id', 'starts_at', 'planned_treatment']);
        $treatments = Treatment::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'price']);
        $planItems = TreatmentPlanItem::query()
            ->whereHas('plan', fn (Builder $query): Builder => $query
                ->where('patient_id', $patient->id)
                ->whereIn('status', [TreatmentPlanStatus::Pending->value, TreatmentPlanStatus::InProgress->value]))
            ->whereIn('status', [TreatmentPlanStatus::Pending->value, TreatmentPlanStatus::InProgress->value])
            ->with(['plan:id,title,patient_id', 'treatment:id,name'])
            ->withCount('patientTreatments')
            ->orderBy('treatment_plan_id')
            ->orderBy('sort_order')
            ->get();

        return view('patient-treatments.create', compact('appointments', 'patient', 'planItems', 'treatments'));
    }

    public function store(StorePatientTreatmentRequest $request, Patient $patient): RedirectResponse
    {
        Gate::authorize('createClinicalRecord', $patient);

        $data = $request->validated();
        $user = $request->user();
        $dentist = $user->dentist;
        $timezone = config('appointments.timezone');
        $performedAt = CarbonImmutable::createFromFormat('!Y-m-d\\TH:i', $data['performed_at'], $timezone);

        if ($performedAt === false) {
            abort(422);
        }

        try {
            $patientTreatment = DB::transaction(function () use ($data, $dentist, $patient, $performedAt, $user): PatientTreatment {
                $treatment = Treatment::query()->lockForUpdate()->findOrFail($data['treatment_id']);
                $appointment = null;

                if (isset($data['appointment_id'])) {
                    $appointment = Appointment::query()->lockForUpdate()->findOrFail($data['appointment_id']);

                    abort_unless(
                        $appointment->patient_id === $patient->id
                            && $appointment->dentist_id === $dentist->id
                            && in_array($appointment->status, [AppointmentStatus::InTreatment, AppointmentStatus::Completed], true),
                        422,
                    );
                }

                $planItem = null;

                if (isset($data['treatment_plan_item_id'])) {
                    $candidate = TreatmentPlanItem::query()->findOrFail($data['treatment_plan_item_id']);
                    $plan = TreatmentPlan::query()->lockForUpdate()->findOrFail($candidate->treatment_plan_id);
                    $planItem = TreatmentPlanItem::query()->lockForUpdate()->findOrFail($candidate->id);
                    $planItem->setRelation('plan', $plan);

                    abort_unless(
                        $planItem->plan->patient_id === $patient->id
                            && in_array($planItem->plan->status, [TreatmentPlanStatus::Pending, TreatmentPlanStatus::InProgress], true)
                            && $planItem->treatment_id === $treatment->id
                            && in_array($planItem->status, [TreatmentPlanStatus::Pending, TreatmentPlanStatus::InProgress], true)
                            && $planItem->patientTreatments()->count() < $planItem->quantity,
                        422,
                    );
                }

                $patientTreatment = PatientTreatment::create([
                    'patient_id' => $patient->id,
                    'dentist_id' => $dentist->id,
                    'treatment_id' => $treatment->id,
                    'appointment_id' => $appointment?->id,
                    'treatment_plan_item_id' => $planItem?->id,
                    'registered_by' => $user->id,
                    'performed_at' => $performedAt->utc(),
                    'procedure_description' => $data['procedure_description'],
                    'clinical_notes' => $data['clinical_notes'] ?? null,
                    'price_applied' => $treatment->price,
                ]);

                PatientTreatmentEvent::create([
                    'patient_treatment_id' => $patientTreatment->id,
                    'user_id' => $user->id,
                    'event_type' => 'created',
                    'changes' => ['price_applied' => ['new' => $patientTreatment->price_applied]],
                ]);

                if ($planItem !== null) {
                    $plan = $planItem->plan;

                    if ($plan->status === TreatmentPlanStatus::Pending) {
                        $plan->update([
                            'status' => TreatmentPlanStatus::InProgress,
                            'updated_by' => $user->id,
                        ]);
                    }

                    $completedProcedures = $planItem->patientTreatments()->count();
                    $planItem->update([
                        'status' => $completedProcedures >= $planItem->quantity
                            ? TreatmentPlanStatus::Completed
                            : TreatmentPlanStatus::InProgress,
                    ]);

                    if (! $plan->items()->where('status', '!=', TreatmentPlanStatus::Completed->value)->exists()) {
                        $plan->update([
                            'status' => TreatmentPlanStatus::Completed,
                            'updated_by' => $user->id,
                        ]);
                    }
                }

                return $patientTreatment;
            });
        } catch (QueryException $exception) {
            if (! in_array($exception->errorInfo[0] ?? null, ['23000', '23505'], true)) {
                throw $exception;
            }

            return back()->withErrors([
                'treatment_id' => 'Este procedimiento ya está asociado a la cita o partida seleccionada.',
            ])->withInput();
        }

        return redirect()->route('pacientes.historial-clinico', $patient)
            ->with('status', 'Procedimiento registrado en el expediente clínico.');
    }

    public function edit(Patient $patient, PatientTreatment $patientTreatment): View
    {
        Gate::authorize('update', $patientTreatment);
        abort_unless($patientTreatment->patient_id === $patient->id, 404);

        return view('patient-treatments.edit', compact('patient', 'patientTreatment'));
    }

    public function update(
        UpdatePatientTreatmentRequest $request,
        Patient $patient,
        PatientTreatment $patientTreatment,
    ): RedirectResponse {
        abort_unless($patientTreatment->patient_id === $patient->id, 404);
        $data = $request->validated();
        $previous = Arr::only($patientTreatment->getOriginal(), ['procedure_description', 'clinical_notes']);

        DB::transaction(function () use ($data, $patientTreatment, $previous, $request): void {
            $patientTreatment->fill($data);
            $changes = [];

            foreach (['procedure_description', 'clinical_notes'] as $field) {
                if ($patientTreatment->isDirty($field)) {
                    $changes[$field] = [
                        'old' => $previous[$field],
                        'new' => $patientTreatment->getAttribute($field),
                    ];
                }
            }

            if ($changes === []) {
                return;
            }

            $patientTreatment->updated_by = $request->user()->id;
            $patientTreatment->save();

            PatientTreatmentEvent::create([
                'patient_treatment_id' => $patientTreatment->id,
                'user_id' => $request->user()->id,
                'event_type' => 'updated',
                'changes' => $changes,
            ]);
        });

        return redirect()->route('pacientes.historial-clinico', $patient)
            ->with('status', 'Procedimiento actualizado y cambio auditado.');
    }
}
