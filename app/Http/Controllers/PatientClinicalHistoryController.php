<?php

namespace App\Http\Controllers;

use App\Enums\TreatmentPlanStatus;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\PatientTreatment;
use App\Models\PatientTreatmentEvent;
use App\Models\Role;
use App\Models\Treatment;
use App\Models\TreatmentPlanItem;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'record_type' => ['nullable', Rule::in(['appointment', 'procedure', 'update'])],
        ]);

        $timezone = config('appointments.timezone');
        $rangeStart = isset($filters['date_from'])
            ? CarbonImmutable::createFromFormat('!Y-m-d', $filters['date_from'], $timezone)->startOfDay()->utc()
            : null;
        $rangeEnd = isset($filters['date_to'])
            ? CarbonImmutable::createFromFormat('!Y-m-d', $filters['date_to'], $timezone)->addDay()->startOfDay()->utc()
            : null;
        $ownDentistId = $request->user()->hasRole(Role::DENTIST) ? $request->user()->dentist?->id : null;

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
            ->when($ownDentistId !== null, fn (Builder $query): Builder => $query->where('dentist_id', $ownDentistId))
            ->when($rangeStart !== null, fn (Builder $query): Builder => $query->where('starts_at', '>=', $rangeStart))
            ->when($rangeEnd !== null, fn (Builder $query): Builder => $query->where('starts_at', '<', $rangeEnd))
            ->when(isset($filters['dentist_id']), fn (Builder $query): Builder => $query->where('dentist_id', $filters['dentist_id']))
            ->orderByDesc('starts_at')
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'appointments')
            ->withQueryString();

        $appointmentActivity = $patient->appointments()
            ->selectRaw("'appointment' as record_type, appointments.id as record_id, starts_at as occurred_at")
            ->when($ownDentistId !== null, fn (Builder $query): Builder => $query->where('dentist_id', $ownDentistId))
            ->when($rangeStart !== null, fn (Builder $query): Builder => $query->where('starts_at', '>=', $rangeStart))
            ->when($rangeEnd !== null, fn (Builder $query): Builder => $query->where('starts_at', '<', $rangeEnd))
            ->when(isset($filters['dentist_id']), fn (Builder $query): Builder => $query->where('dentist_id', $filters['dentist_id']));
        $procedureActivity = $patient->patientTreatments()
            ->selectRaw("'procedure' as record_type, patient_treatments.id as record_id, performed_at as occurred_at")
            ->when($rangeStart !== null, fn (Builder $query): Builder => $query->where('performed_at', '>=', $rangeStart))
            ->when($rangeEnd !== null, fn (Builder $query): Builder => $query->where('performed_at', '<', $rangeEnd))
            ->when(isset($filters['dentist_id']), fn (Builder $query): Builder => $query->where('dentist_id', $filters['dentist_id']))
            ->when(isset($filters['treatment_id']), fn (Builder $query): Builder => $query->where('treatment_id', $filters['treatment_id']));
        $updateActivity = PatientTreatmentEvent::query()
            ->selectRaw("'update' as record_type, patient_treatment_events.id as record_id, patient_treatment_events.created_at as occurred_at")
            ->where('event_type', 'updated')
            ->whereHas('patientTreatment', fn (Builder $query): Builder => $query
                ->where('patient_id', $patient->id)
                ->when(isset($filters['dentist_id']), fn (Builder $query): Builder => $query->where('dentist_id', $filters['dentist_id']))
                ->when(isset($filters['treatment_id']), fn (Builder $query): Builder => $query->where('treatment_id', $filters['treatment_id'])))
            ->when($rangeStart !== null, fn (Builder $query): Builder => $query->where('patient_treatment_events.created_at', '>=', $rangeStart))
            ->when($rangeEnd !== null, fn (Builder $query): Builder => $query->where('patient_treatment_events.created_at', '<', $rangeEnd));

        $activityQuery = $procedureActivity->toBase()->unionAll($updateActivity->toBase());

        if (! isset($filters['treatment_id'])) {
            $activityQuery->unionAll($appointmentActivity->toBase());
        }

        $activity = DB::query()
            ->fromSub($activityQuery, 'activity')
            ->when(isset($filters['record_type']), fn ($query) => $query->where('record_type', $filters['record_type']))
            ->orderByDesc('occurred_at')
            ->orderByDesc('record_type')
            ->orderByDesc('record_id')
            ->paginate(12, ['*'], 'activity')
            ->withQueryString();
        $activityRows = collect($activity->items());
        $activityAppointments = Appointment::query()
            ->with(['dentist:id,user_id', 'dentist.user:id,name'])
            ->whereKey($activityRows->where('record_type', 'appointment')->pluck('record_id'))
            ->get()
            ->keyBy('id');
        $activityProcedures = PatientTreatment::query()
            ->with(['dentist:id,user_id', 'dentist.user:id,name', 'treatment:id,name', 'appointment:id,patient_id,dentist_id,starts_at'])
            ->whereKey($activityRows->where('record_type', 'procedure')->pluck('record_id'))
            ->get()
            ->keyBy('id');
        $activityUpdates = PatientTreatmentEvent::query()
            ->with(['patientTreatment.treatment:id,name', 'user:id,name'])
            ->whereKey($activityRows->where('record_type', 'update')->pluck('record_id'))
            ->get()
            ->keyBy('id');

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

        $plans = $patient->treatmentPlans()
            ->with([
                'dentist:id,user_id',
                'dentist.user:id,name',
                'items' => fn ($query) => $query->limit(5),
                'items.treatment:id,name',
            ])
            ->withCount(['items', 'items as completed_items_count' => fn (Builder $query): Builder => $query
                ->where('status', TreatmentPlanStatus::Completed->value)])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(5, ['*'], 'plans')
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
            'activity',
            'activityAppointments',
            'activityProcedures',
            'activityUpdates',
            'appointments',
            'dentists',
            'filters',
            'ownDentistId',
            'patient',
            'pendingPlanItems',
            'plans',
            'procedures',
            'treatments',
            'timezone',
        ));
    }
}
