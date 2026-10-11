<?php

namespace App\Http\Controllers;

use App\Actions\RegisterPayment;
use App\Enums\PaymentMethod;
use App\Http\Requests\StorePaymentRequest;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(): View
    {
        $payments = Payment::query()
            ->with('invoice.sale.customer')
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->paginate(15);

        return view('payments.index', compact('payments'));
    }

    public function show(Payment $payment): View
    {
        $payment->load('invoice.sale.customer');

        return view('payments.show', compact('payment'));
    }

    public function store(
        Invoice $invoice,
        StorePaymentRequest $request,
        RegisterPayment $registerPayment,
    ): RedirectResponse {
        $data = $request->validated();
        $payment = $registerPayment->handle(
            $invoice,
            (string) $data['amount'],
            PaymentMethod::from($data['method']),
            $data['payment_date'],
            $data['notes'] ?? null,
        );

        return redirect()
            ->route('pagos.show', $payment)
            ->with('status', 'El pago se registró correctamente.');
    }

    public function receipt(Payment $payment): View
    {
        $payment->load('invoice.sale.customer');

        return view('documents.receipt', compact('payment'));
    }
}
