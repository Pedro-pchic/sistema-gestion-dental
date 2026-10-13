<?php

namespace App\Http\Controllers;

use App\Enums\TreatmentPlanStatus;
use App\Http\Requests\StoreTreatmentPlanRequest;
use App\Http\Requests\UpdateTreatmentPlanItemRequest;
use App\Http\Requests\UpdateTreatmentPlanRequest;
use App\Models\Patient;
use App\Models\Treatment;
use App\Models\TreatmentPlan;
use App\Models\TreatmentPlanItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TreatmentPlanController extends Controller
{
    public function index(Patient $patient): View
    {
        Gate::authorize('viewClinicalHistory', $patient);

        $plans = $patient->treatmentPlans()
            ->with([
                'dentist:id,user_id',
                'dentist.user:id,name',
                'items.treatment:id,name',
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('treatment-plans.index', compact('patient', 'plans'));
    }

    public function create(Patient $patient): View
    {
        Gate::authorize('create', [TreatmentPlan::class, $patient]);

        $treatments = Treatment::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'price']);

        return view('treatment-plans.create', compact('patient', 'treatments'));
    }

    public function store(StoreTreatmentPlanRequest $request, Patient $patient): RedirectResponse
    {
        $data = $request->validated();
        $items = $data['items'];
        $user = $request->user();
        $dentist = $user->dentist;

        $plan = DB::transaction(function () use ($data, $dentist, $items, $patient, $user): TreatmentPlan {
            $treatmentsById = Treatment::query()
                ->whereKey(array_column($items, 'treatment_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $plan = TreatmentPlan::create([
                'patient_id' => $patient->id,
                'dentist_id' => $dentist->id,
                'created_by' => $user->id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'status' => TreatmentPlanStatus::Pending,
            ]);

            foreach ($items as $position => $item) {
                $treatment = $treatmentsById->get((int) $item['treatment_id']);

                abort_unless($treatment !== null && $treatment->is_active, 422);

                $plan->items()->create([
                    'treatment_id' => $treatment->id,
                    'quantity' => $item['quantity'],
                    'estimated_unit_price' => $item['estimated_unit_price'] ?? $treatment->price,
                    'status' => TreatmentPlanStatus::Pending,
                    'sort_order' => $position,
                ]);
            }

            return $plan;
        });

        return redirect()->route('pacientes.planes.show', [$patient, $plan])
            ->with('status', 'Plan de tratamiento creado correctamente.');
    }

    public function show(Patient $patient, TreatmentPlan $treatmentPlan): View
    {
        Gate::authorize('view', $treatmentPlan);
        abort_unless($treatmentPlan->patient_id === $patient->id, 404);

        $treatmentPlan->load([
            'dentist:id,user_id',
            'dentist.user:id,name',
            'creator:id,name',
            'items.treatment:id,name',
            'items.patientTreatments:id,treatment_plan_item_id,performed_at,price_applied',
        ]);

        return view('treatment-plans.show', compact('patient', 'treatmentPlan'));
    }

    public function update(UpdateTreatmentPlanRequest $request, Patient $patient, TreatmentPlan $treatmentPlan): RedirectResponse
    {
        abort_unless($treatmentPlan->patient_id === $patient->id, 404);
        $newStatus = TreatmentPlanStatus::from($request->validated('status'));

        DB::transaction(function () use ($newStatus, $request, $treatmentPlan): void {
            $lockedPlan = TreatmentPlan::query()->lockForUpdate()->findOrFail($treatmentPlan->id);

            abort_unless($lockedPlan->status->canTransitionTo($newStatus), 422);

            if (
                $newStatus === TreatmentPlanStatus::Completed
                && $lockedPlan->items()->where('status', '!=', TreatmentPlanStatus::Completed->value)->exists()
            ) {
                abort(422);
            }

            $lockedPlan->update([
                'status' => $newStatus,
                'updated_by' => $request->user()->id,
            ]);

            if ($newStatus === TreatmentPlanStatus::Cancelled) {
                $lockedPlan->items()
                    ->whereIn('status', [TreatmentPlanStatus::Pending->value, TreatmentPlanStatus::InProgress->value])
                    ->update(['status' => TreatmentPlanStatus::Cancelled]);
            }
        });

        return back()->with('status', 'Estado del plan actualizado.');
    }

    public function updateItem(
        UpdateTreatmentPlanItemRequest $request,
        Patient $patient,
        TreatmentPlan $treatmentPlan,
        TreatmentPlanItem $treatmentPlanItem,
    ): RedirectResponse {
        abort_unless($treatmentPlan->patient_id === $patient->id, 404);
        $newStatus = TreatmentPlanStatus::from($request->validated('status'));

        DB::transaction(function () use ($newStatus, $request, $treatmentPlan, $treatmentPlanItem): void {
            $plan = TreatmentPlan::query()->lockForUpdate()->findOrFail($treatmentPlan->id);
            $item = $plan->items()->lockForUpdate()->findOrFail($treatmentPlanItem->id);

            abort_unless(
                in_array($plan->status, [TreatmentPlanStatus::Pending, TreatmentPlanStatus::InProgress], true)
                    && $item->status->canTransitionTo($newStatus),
                422,
            );

            $item->update(['status' => $newStatus]);

            if ($plan->status === TreatmentPlanStatus::Pending && $newStatus === TreatmentPlanStatus::InProgress) {
                $plan->update([
                    'status' => TreatmentPlanStatus::InProgress,
                    'updated_by' => $request->user()->id,
                ]);
            } else {
                $plan->update(['updated_by' => $request->user()->id]);
            }
        });

        return back()->with('status', 'Estado de la partida actualizado.');
    }
}
