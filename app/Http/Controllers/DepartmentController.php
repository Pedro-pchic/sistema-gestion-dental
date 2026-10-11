<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(): View
    {
        $departments = Department::withCount('positions')->orderBy('name')->paginate(15);

        return view('departments.index', compact('departments'));
    }

    public function create(): View
    {
        return view('departments.create');
    }

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $data['is_active'] ?? true;
        $department = Department::create($data);

        return redirect()->route('departamentos.show', $department)->with('status', 'Departamento creado correctamente.');
    }

    public function show(Department $department): View
    {
        $department->loadCount('positions');

        return view('departments.show', compact('department'));
    }

    public function edit(Department $department): View
    {
        return view('departments.edit', compact('department'));
    }

    public function update(UpdateDepartmentRequest $request, Department $department): RedirectResponse
    {
        $department->update($request->validated());

        return redirect()->route('departamentos.show', $department)->with('status', 'Departamento actualizado correctamente.');
    }

    public function toggleStatus(Department $department): RedirectResponse
    {
        $department->update(['is_active' => ! $department->is_active]);

        return back()->with('status', 'Estado del departamento actualizado.');
    }
}
