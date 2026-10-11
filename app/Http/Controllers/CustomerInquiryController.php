<?php

namespace App\Http\Controllers;

use App\Enums\CustomerInquiryStatus;
use App\Http\Requests\StoreCustomerInquiryRequest;
use App\Http\Requests\UpdateCustomerInquiryRequest;
use App\Models\CustomerInquiry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerInquiryController extends Controller
{
    public function create(): View
    {
        return view('portal.contact');
    }

    public function store(StoreCustomerInquiryRequest $request): RedirectResponse
    {
        CustomerInquiry::create($request->validated());

        return redirect()
            ->route('contacto.create')
            ->with('status', 'Recibimos tu consulta. Nuestro equipo se pondrá en contacto contigo.');
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(CustomerInquiryStatus::class)],
        ]);
        $status = isset($filters['status']) ? CustomerInquiryStatus::from($filters['status']) : null;

        $inquiries = CustomerInquiry::query()
            ->with('customer')
            ->when(
                $status !== null,
                fn (Builder $query): Builder => $query->where('status', $status),
            )
            ->latest()
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('inquiries.index', [
            'inquiries' => $inquiries,
            'status' => $status,
            'statuses' => CustomerInquiryStatus::cases(),
        ]);
    }

    public function show(CustomerInquiry $inquiry): View
    {
        $inquiry->load('customer');

        return view('inquiries.show', [
            'inquiry' => $inquiry,
            'statuses' => CustomerInquiryStatus::cases(),
        ]);
    }

    public function update(
        UpdateCustomerInquiryRequest $request,
        CustomerInquiry $inquiry,
    ): RedirectResponse {
        $inquiry->update($request->validated());

        return redirect()
            ->route('consultas.show', $inquiry)
            ->with('status', 'Estado de la consulta actualizado.');
    }
}
