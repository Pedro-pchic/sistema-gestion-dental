<?php

namespace App\Http\Controllers;

use App\Enums\TreatmentPlanStatus;
use App\Models\Patient;
use App\Models\Treatment;
use App\Models\TreatmentPlanItem;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PatientClinicalHistoryController extends Controller
{
    public function show(Request $request, Patient $patient): View
    {
        Gate::authorize('viewClinicalHistory', $patient);

        $filters = $request->validate([
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'dentist_id' => ['nullable', 'integer', Rule::exists('dentists', 'id')],
            'treatment_id' => ['nullable', 'integer', Rule::exists('treatments', 'id')],
        ]);

        $timezone = config('appointments.timezone');
        $rangeStart = isset($filters['date_from'])
            ? CarbonImmutable::createFromFormat('!Y-m-d', $filters['date_from'], $timezone)->startOfDay()->utc()
            : null;
        $rangeEnd = isset($filters['date_to'])
            ? CarbonImmutable::createFromFormat('!Y-m-d', $filters['date_to'], $timezone)->addDay()->startOfDay()->utc()
            : null;

        $procedures = $patient->patientTreatments()
            ->with([
                'dentist:id,user_id',
                'dentist.user:id,name',
                'treatment:id,name',
                'appointment:id,patient_id,dentist_id,starts_at',
                'planItem.plan:id,title,patient_id',
            ])
            ->when($rangeStart !== null, fn (Builder $query): Builder => $query->where('performed_at', '>=', $rangeStart))
            ->when($rangeEnd !== null, fn (Builder $query): Builder => $query->where('performed_at', '<', $rangeEnd))
            ->when(isset($filters['dentist_id']), fn (Builder $query): Builder => $query->where('dentist_id', $filters['dentist_id']))
            ->when(isset($filters['treatment_id']), fn (Builder $query): Builder => $query->where('treatment_id', $filters['treatment_id']))
            ->orderByDesc('performed_at')
            ->orderByDesc('id')
            ->paginate(15, ['*'], 'procedures')
            ->withQueryString();

        $appointments = $patient->appointments()
            ->with(['dentist:id,user_id', 'dentist.user:id,name'])
            ->when($rangeStart !== null, fn (Builder $query): Builder => $query->where('starts_at', '>=', $rangeStart))
            ->when($rangeEnd !== null, fn (Builder $query): Builder => $query->where('starts_at', '<', $rangeEnd))
            ->when(isset($filters['dentist_id']), fn (Builder $query): Builder => $query->where('dentist_id', $filters['dentist_id']))
            ->orderByDesc('starts_at')
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'appointments')
            ->withQueryString();

        $pendingPlanItems = TreatmentPlanItem::query()
            ->whereHas('plan', fn (Builder $query): Builder => $query
                ->where('patient_id', $patient->id)
                ->whereIn('status', [TreatmentPlanStatus::Pending->value, TreatmentPlanStatus::InProgress->value]))
            ->whereIn('status', [TreatmentPlanStatus::Pending->value, TreatmentPlanStatus::InProgress->value])
            ->with(['plan:id,title,patient_id,status', 'treatment:id,name'])
            ->orderBy('treatment_plan_id')
            ->orderBy('sort_order')
            ->paginate(10, ['*'], 'pending')
            ->withQueryString();

        $dentists = $patient->dentists()
            ->with('user:id,name')
            ->orderBy('dentists.id')
            ->get();
        $treatments = Treatment::query()
            ->whereHas('patientTreatments', fn (Builder $query): Builder => $query->where('patient_id', $patient->id))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('patients.clinical-history', compact(
            'appointments',
            'dentists',
            'filters',
            'patient',
            'pendingPlanItems',
            'procedures',
            'treatments',
            'timezone',
        ));
    }
}
