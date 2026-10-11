<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Models\Dentist;
use App\Models\Patient;
use App\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PatientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Patient::class);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $user = $request->user();

        $patientsQuery = Patient::query()
            ->search($filters['search'] ?? null)
            ->orderBy('last_name')
            ->orderBy('first_name');

        if ($user->hasRole(Role::DENTIST)) {
            $patientsQuery->whereHas('dentists', function (Builder $query) use ($user): void {
                $query->where('dentists.user_id', $user->id)
                    ->where('dentists.is_active', true);
            });
        }

        $patients = $patientsQuery->paginate(15)->withQueryString();

        return view('patients.index', [
            'patients' => $patients,
            'search' => $filters['search'] ?? '',
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        Gate::authorize('create', Patient::class);
        $canAssignDentists = $request->user()->can('manage-patient-assignments');

        return view('patients.create', [
            'canAssignDentists' => $canAssignDentists,
            'dentists' => $canAssignDentists
                ? Dentist::query()->with('user')->where('is_active', true)->orderBy('id')->get()
                : collect(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePatientRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $dentistIds = $data['dentist_ids'] ?? [];
        unset($data['dentist_ids']);

        $patient = DB::transaction(function () use ($data, $dentistIds, $request): Patient {
            $patient = Patient::create($data);

            if ($request->user()->can('manage-patient-assignments')) {
                $patient->dentists()->sync($dentistIds);
            }

            return $patient;
        });

        return redirect()
            ->route('pacientes.show', $patient)
            ->with('status', 'Paciente registrado correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Patient $patient): View
    {
        Gate::authorize('view', $patient);
        $patient->load('dentists.user');

        return view('patients.show', compact('patient'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Patient $patient): View
    {
        Gate::authorize('update', $patient);
        $canAssignDentists = $request->user()->can('manage-patient-assignments');

        return view('patients.edit', [
            'patient' => $patient,
            'canAssignDentists' => $canAssignDentists,
            'dentists' => $canAssignDentists
                ? Dentist::query()->with('user')->where('is_active', true)->orderBy('id')->get()
                : collect(),
            'assignedDentistIds' => $canAssignDentists
                ? $patient->dentists()->pluck('dentists.id')->all()
                : [],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePatientRequest $request, Patient $patient): RedirectResponse
    {
        $data = $request->validated();
        $shouldSyncDentists = $request->user()->can('manage-patient-assignments')
            && array_key_exists('dentist_ids_present', $data);
        $dentistIds = $data['dentist_ids'] ?? [];
        unset($data['dentist_ids'], $data['dentist_ids_present']);

        DB::transaction(function () use ($data, $dentistIds, $shouldSyncDentists, $patient): void {
            $patient->update($data);

            if ($shouldSyncDentists) {
                $patient->dentists()->sync($dentistIds);
            }
        });

        return redirect()
            ->route('pacientes.show', $patient)
            ->with('status', 'Paciente actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Patient $patient): RedirectResponse
    {
        Gate::authorize('delete', $patient);
        $patient->delete();

        return redirect()
            ->route('pacientes.index')
            ->with('status', 'Paciente eliminado lógicamente.');
    }

    public function toggleStatus(Patient $patient): RedirectResponse
    {
        Gate::authorize('update', $patient);
        $patient->update(['is_active' => ! $patient->is_active]);

        return back()->with('status', 'Estado del paciente actualizado.');
    }
}
