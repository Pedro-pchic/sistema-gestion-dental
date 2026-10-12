<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTreatmentRequest;
use App\Http\Requests\UpdateTreatmentRequest;
use App\Models\Treatment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TreatmentController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Treatment::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $search = trim((string) ($filters['search'] ?? ''));
        $treatments = Treatment::query()
            ->when($search !== '', fn (Builder $query): Builder => $query
                ->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($search).'%']))
            ->when(isset($filters['status']), fn (Builder $query): Builder => $query
                ->where('is_active', $filters['status'] === 'active'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('treatments.index', compact('filters', 'treatments'));
    }

    public function create(): View
    {
        Gate::authorize('create', Treatment::class);

        return view('treatments.create');
    }

    public function store(StoreTreatmentRequest $request): RedirectResponse
    {
        $treatment = Treatment::create($request->validated() + ['is_active' => true]);

        return redirect()->route('tratamientos.show', $treatment)
            ->with('status', 'Tratamiento registrado correctamente.');
    }

    public function show(Treatment $treatment): View
    {
        Gate::authorize('view', $treatment);

        return view('treatments.show', compact('treatment'));
    }

    public function edit(Treatment $treatment): View
    {
        Gate::authorize('update', $treatment);

        return view('treatments.edit', compact('treatment'));
    }

    public function update(UpdateTreatmentRequest $request, Treatment $treatment): RedirectResponse
    {
        $treatment->update($request->validated());

        return redirect()->route('tratamientos.show', $treatment)
            ->with('status', 'Tratamiento actualizado correctamente.');
    }

    public function toggleStatus(Request $request, Treatment $treatment): RedirectResponse
    {
        Gate::authorize('toggleStatus', $treatment);
        $treatment->update(['is_active' => ! $treatment->is_active]);

        return back()->with('status', 'Estado del tratamiento actualizado.');
    }
}
