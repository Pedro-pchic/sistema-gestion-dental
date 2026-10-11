<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePositionRequest;
use App\Http\Requests\UpdatePositionRequest;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PositionController extends Controller
{
    public function index(): View
    {
        $positions = Position::with('department')
            ->withCount('employees')
            ->orderBy('name')
            ->paginate(15);

        return view('positions.index', compact('positions'));
    }

    public function create(): View
    {
        $departments = Department::orderBy('name')->get();

        return view('positions.create', compact('departments'));
    }

    public function store(StorePositionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $data['is_active'] ?? true;
        $position = Position::create($data);

        return redirect()->route('puestos.show', $position)->with('status', 'Puesto creado correctamente.');
    }

    public function show(Position $position): View
    {
        $position->load('department')->loadCount('employees');

        return view('positions.show', compact('position'));
    }

    public function edit(Position $position): View
    {
        $departments = Department::orderBy('name')->get();

        return view('positions.edit', compact('departments', 'position'));
    }

    public function update(UpdatePositionRequest $request, Position $position): RedirectResponse
    {
        $position->update($request->validated());

        return redirect()->route('puestos.show', $position)->with('status', 'Puesto actualizado correctamente.');
    }

    public function toggleStatus(Position $position): RedirectResponse
    {
        $position->update(['is_active' => ! $position->is_active]);

        return back()->with('status', 'Estado del puesto actualizado.');
    }
}
