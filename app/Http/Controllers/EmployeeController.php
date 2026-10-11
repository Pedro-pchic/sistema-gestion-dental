<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $employees = Employee::with(['user', 'position.department'])
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->paginate(15);

        return view('employees.index', compact('employees'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $users = User::whereDoesntHave('employee')->orderBy('name')->get();
        $positions = Position::with('department')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('employees.create', compact('positions', 'users'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $data['is_active'] ?? true;

        Employee::create($data);

        return redirect()->route('empleados.index')->with('status', 'Empleado creado correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Employee $employee): View
    {
        $employee->load(['user', 'position.department']);

        return view('employees.show', compact('employee'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Employee $employee): View
    {
        $users = User::whereDoesntHave('employee')
            ->orWhere('id', $employee->user_id)
            ->orderBy('name')
            ->get();
        $positionsQuery = Position::with('department')->where('is_active', true);

        if ($employee->position_id !== null) {
            $positionsQuery->orWhereKey($employee->position_id);
        }

        $positions = $positionsQuery->orderBy('name')->get();

        return view('employees.edit', compact('employee', 'positions', 'users'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $employee->update($request->validated());

        return redirect()->route('empleados.show', $employee)->with('status', 'Empleado actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employee $employee): RedirectResponse
    {
        abort(405);
    }

    public function toggleStatus(Employee $employee): RedirectResponse
    {
        $employee->update(['is_active' => ! $employee->is_active]);

        return back()->with('status', 'Estado del empleado actualizado.');
    }
}
