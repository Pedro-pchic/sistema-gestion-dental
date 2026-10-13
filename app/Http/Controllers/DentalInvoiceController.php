<?php

namespace App\Http\Controllers;

use App\Actions\CancelDentalInvoice;
use App\Actions\CreateDentalInvoiceDraft;
use App\Actions\IssueDentalInvoice;
use App\Enums\DentalInvoiceStatus;
use App\Http\Requests\CancelDentalInvoiceRequest;
use App\Http\Requests\StoreDentalInvoiceRequest;
use App\Models\DentalInvoice;
use App\Models\DentalInvoiceDetail;
use App\Models\Patient;
use App\Models\PatientTreatment;
use App\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DentalInvoiceController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', DentalInvoice::class);

        $filters = $request->validate([
            'status' => ['nullable', 'string', 'in:draft,issued,partially_paid,paid,cancelled'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $search = trim($filters['search'] ?? '');

        $invoices = DentalInvoice::query()
            ->with('patient:id,first_name,last_name,record_number')
            ->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('number', 'like', '%'.$search.'%')
                        ->orWhereHas('patient', fn (Builder $patientQuery): Builder => $patientQuery->search($search));
                });
            })
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('dental-invoices.index', compact('invoices', 'filters'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', DentalInvoice::class);

        $filters = validator($request->query(), [
            'search' => ['nullable', 'string', 'max:100'],
            'patient_id' => ['nullable', 'integer', 'exists:patients,id'],
        ])->validate();
        $search = trim($filters['search'] ?? '');
        $patients = $search === ''
            ? collect()
            : Patient::query()
                ->where('is_active', true)
                ->search($search)
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->limit(40)
                ->get(['id', 'first_name', 'last_name', 'record_number']);

        $patient = isset($filters['patient_id'])
            ? Patient::query()->findOrFail($filters['patient_id'])
            : null;

        if ($patient !== null && ! $patients->contains('id', $patient->getKey())) {
            $patients->prepend($patient);
        }

        $procedures = $patient === null
            ? collect()
            : PatientTreatment::query()
                ->where('patient_id', $patient->getKey())
                ->whereDoesntHave('dentalInvoiceDetails.invoice', fn (Builder $query): Builder => $query
                    ->where('status', '!=', DentalInvoiceStatus::Cancelled->value))
                ->with('treatment:id,name')
                ->orderByDesc('performed_at')
                ->get(['id', 'patient_id', 'treatment_id', 'performed_at', 'price_applied']);

        return view('dental-invoices.create', compact('patients', 'patient', 'procedures', 'search'));
    }

    public function store(StoreDentalInvoiceRequest $request, CreateDentalInvoiceDraft $createDentalInvoiceDraft): RedirectResponse
    {
        $data = $request->validated();
        $patient = Patient::query()->findOrFail($data['patient_id']);
        $invoice = $createDentalInvoiceDraft->handle(
            $patient,
            $data['patient_treatment_ids'],
            $request->user(),
        );

        return redirect()
            ->route('facturacion.facturas.show', $invoice)
            ->with('status', 'Borrador de factura creado. Revisa los conceptos y emítelo para registrar el documento.');
    }

    public function show(DentalInvoice $dentalInvoice): View
    {
        Gate::authorize('view', $dentalInvoice);
        $dentalInvoice->load([
            'patient:id,first_name,last_name,record_number,phone,email,address',
            'creator:id,name',
            'issuer:id,name',
            'details.patientTreatment:id,treatment_id,performed_at',
            'payments.recorder:id,name',
            'payments.canceller:id,name',
        ]);

        return view('dental-invoices.show', [
            'invoice' => $dentalInvoice,
            'paymentRequestKey' => (string) Str::uuid(),
        ]);
    }

    public function issue(DentalInvoice $dentalInvoice, IssueDentalInvoice $issueDentalInvoice): RedirectResponse
    {
        Gate::authorize('issue', $dentalInvoice);
        $issueDentalInvoice->handle($dentalInvoice, request()->user());

        return redirect()
            ->route('facturacion.facturas.show', $dentalInvoice)
            ->with('status', 'Factura emitida correctamente.');
    }

    public function cancel(
        CancelDentalInvoiceRequest $request,
        DentalInvoice $dentalInvoice,
        CancelDentalInvoice $cancelDentalInvoice,
    ): RedirectResponse {
        $cancelDentalInvoice->handle($dentalInvoice, $request->user(), $request->validated('reason'));

        return redirect()
            ->route('facturacion.facturas.show', $dentalInvoice)
            ->with('status', 'Factura anulada con trazabilidad.');
    }

    public function document(DentalInvoice $dentalInvoice): View|RedirectResponse
    {
        Gate::authorize('view', $dentalInvoice);

        if ($dentalInvoice->number === null || $dentalInvoice->status === DentalInvoiceStatus::Draft) {
            return redirect()
                ->route('facturacion.facturas.show', $dentalInvoice)
                ->withErrors(['invoice' => 'Emite la factura antes de imprimir el comprobante.']);
        }

        $dentalInvoice->load([
            'patient:id,first_name,last_name,record_number,phone,email,address',
            'details:id,dental_invoice_id,description,quantity,unit_price,subtotal',
        ]);

        return view('documents.dental-invoice', ['invoice' => $dentalInvoice]);
    }

    public function patientSummary(Request $request, Patient $patient): View
    {
        Gate::authorize('viewFinancialSummary', $patient);

        $detailsQuery = DentalInvoiceDetail::query()
            ->whereHas('invoice', fn (Builder $query): Builder => $query
                ->where('patient_id', $patient->getKey())
                ->where('status', '!=', DentalInvoiceStatus::Draft->value));
        $isDentist = $request->user()->hasRole(Role::DENTIST);

        if ($isDentist) {
            $detailsQuery
                ->whereHas('invoice', fn (Builder $query): Builder => $query
                    ->where('status', '!=', DentalInvoiceStatus::Cancelled->value))
                ->whereHas('patientTreatment.dentist', fn (Builder $query): Builder => $query
                    ->where('user_id', $request->user()->getKey()));
        }

        $details = $detailsQuery
            ->with([
                'invoice:id,patient_id,number,status,issue_date',
                'patientTreatment:id,treatment_id,dentist_id,performed_at',
                'patientTreatment.treatment:id,name',
            ])
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('dental-invoices.patient-summary', compact('patient', 'details', 'isDentist'));
    }
}
