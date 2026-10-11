<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(): View
    {
        $customers = Customer::query()
            ->withCount(['addresses', 'quotes'])
            ->orderBy('name')
            ->paginate(15);

        return view('customers.index', compact('customers'));
    }

    public function create(): View
    {
        return view('customers.create');
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $data['is_active'] ?? true;
        $customer = Customer::create($data);

        return redirect()
            ->route('clientes.show', $customer)
            ->with('status', 'Cliente creado correctamente.');
    }

    public function show(Customer $customer): View
    {
        $customer->setRelation(
            'addresses',
            $customer->addresses()->orderByDesc('is_default')->orderBy('id')->get(),
        );
        $customer->loadCount('quotes');

        return view('customers.show', compact('customer'));
    }

    public function edit(Customer $customer): View
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());

        return redirect()
            ->route('clientes.show', $customer)
            ->with('status', 'Cliente actualizado correctamente.');
    }

    public function toggleStatus(Customer $customer): RedirectResponse
    {
        $customer->update(['is_active' => ! $customer->is_active]);

        return back()->with('status', 'Estado del cliente actualizado.');
    }
}
