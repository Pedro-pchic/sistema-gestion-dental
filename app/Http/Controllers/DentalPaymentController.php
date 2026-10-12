<?php

namespace App\Http\Controllers;

use App\Actions\CancelDentalPayment;
use App\Actions\RegisterDentalPayment;
use App\Http\Requests\CancelDentalPaymentRequest;
use App\Http\Requests\StoreDentalPaymentRequest;
use App\Models\DentalInvoice;
use App\Models\DentalPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DentalPaymentController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', DentalPayment::class);

        $payments = DentalPayment::query()
            ->with([
                'invoice:id,patient_id,number',
                'invoice.patient:id,first_name,last_name',
                'recorder:id,name',
            ])
            ->latest('payment_date')
            ->latest('id')
            ->paginate(20);

        return view('dental-payments.index', compact('payments'));
    }

    public function show(DentalPayment $dentalPayment): View
    {
        Gate::authorize('view', $dentalPayment);
        $dentalPayment->load([
            'invoice:id,patient_id,number,status,total,balance_due',
            'invoice.patient:id,first_name,last_name,record_number',
            'recorder:id,name',
            'canceller:id,name',
        ]);

        return view('dental-payments.show', ['payment' => $dentalPayment]);
    }

    public function store(
        StoreDentalPaymentRequest $request,
        DentalInvoice $dentalInvoice,
        RegisterDentalPayment $registerDentalPayment,
    ): RedirectResponse {
        $payment = $registerDentalPayment->handle($dentalInvoice, $request->validated(), $request->user());

        return redirect()
            ->route('facturacion.pagos.show', $payment)
            ->with('status', 'Pago registrado correctamente.');
    }

    public function receipt(DentalPayment $dentalPayment): View
    {
        Gate::authorize('view', $dentalPayment);
        $dentalPayment->load([
            'invoice:id,patient_id,number',
            'invoice.patient:id,first_name,last_name,record_number',
        ]);

        return view('documents.dental-receipt', ['payment' => $dentalPayment]);
    }

    public function cancel(
        CancelDentalPaymentRequest $request,
        DentalPayment $dentalPayment,
        CancelDentalPayment $cancelDentalPayment,
    ): RedirectResponse {
        $cancelDentalPayment->handle($dentalPayment, $request->user(), $request->validated('reason'));

        return redirect()
            ->route('facturacion.pagos.show', $dentalPayment)
            ->with('status', 'Pago anulado con trazabilidad.');
    }
}
