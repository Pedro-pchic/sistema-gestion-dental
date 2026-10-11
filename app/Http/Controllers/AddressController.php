<?php

namespace App\Http\Controllers;

use App\Actions\SaveAddress;
use App\Http\Requests\StoreAddressRequest;
use App\Http\Requests\UpdateAddressRequest;
use App\Models\Address;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AddressController extends Controller
{
    public function create(Customer $customer): View
    {
        return view('customers.addresses.create', compact('customer'));
    }

    public function store(
        StoreAddressRequest $request,
        Customer $customer,
        SaveAddress $saveAddress,
    ): RedirectResponse {
        $saveAddress->handle($customer, $request->validated());

        return redirect()
            ->route('clientes.show', $customer)
            ->with('status', 'Dirección agregada correctamente.');
    }

    public function edit(Customer $customer, Address $address): View
    {
        return view('customers.addresses.edit', compact('address', 'customer'));
    }

    public function update(
        UpdateAddressRequest $request,
        Customer $customer,
        Address $address,
        SaveAddress $saveAddress,
    ): RedirectResponse {
        $saveAddress->handle($customer, $request->validated(), $address);

        return redirect()
            ->route('clientes.show', $customer)
            ->with('status', 'Dirección actualizada correctamente.');
    }

    public function destroy(Customer $customer, Address $address): RedirectResponse
    {
        $customer->addresses()->whereKey($address->getKey())->delete();

        return redirect()
            ->route('clientes.show', $customer)
            ->with('status', 'Dirección eliminada correctamente.');
    }
}
