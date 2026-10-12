<?php

namespace Tests\Feature;

use App\Enums\DentalInvoiceStatus;
use App\Enums\DentalPaymentStatus;
use App\Models\DentalInvoice;
use App\Models\DentalPayment;
use App\Models\Dentist;
use App\Models\Patient;
use App\Models\PatientTreatment;
use App\Models\Role;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DentalBillingManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_invoice_draft_uses_only_performed_procedures_and_keeps_historical_price(): void
    {
        $administrator = $this->userWithRole(Role::ADMINISTRATOR);
        $patient = Patient::factory()->create();
        $procedure = $this->procedure($patient, '350.00', 'Resina dental', 'NOTA CLINICA PRIVADA');

        $invoice = $this->createInvoiceDraft($administrator, $patient, [$procedure]);

        $detail = $invoice->details()->firstOrFail();
        $this->assertSame(DentalInvoiceStatus::Draft, $invoice->status);
        $this->assertSame($procedure->getKey(), $detail->patient_treatment_id);
        $this->assertSame('Resina dental', $detail->description);
        $this->assertSame('350.00', $detail->unit_price);
        $this->assertSame('350.00', $invoice->subtotal);

        $procedure->update(['price_applied' => '500.00']);
        $this->actingAs($administrator)
            ->post(route('facturacion.facturas.issue', $invoice))
            ->assertRedirectToRoute('facturacion.facturas.show', $invoice);

        $invoice->refresh();
        $this->assertSame(DentalInvoiceStatus::Issued, $invoice->status);
        $this->assertSame('350.00', $invoice->total);
        $this->assertSame('350.00', $invoice->details()->firstOrFail()->unit_price);
        $this->assertSame($administrator->getKey(), $invoice->issued_by);
    }

    public function test_empty_and_already_reserved_procedures_cannot_be_invoiced(): void
    {
        $administrator = $this->userWithRole(Role::ADMINISTRATOR);
        $patient = Patient::factory()->create();
        $procedure = $this->procedure($patient, '125.00', 'Limpieza dental');

        $this->actingAs($administrator)
            ->post(route('facturacion.facturas.store'), [
                'patient_id' => $patient->getKey(),
                'patient_treatment_ids' => [],
            ])
            ->assertSessionHasErrors('patient_treatment_ids');

        $this->createInvoiceDraft($administrator, $patient, [$procedure]);

        $this->actingAs($administrator)
            ->post(route('facturacion.facturas.store'), [
                'patient_id' => $patient->getKey(),
                'patient_treatment_ids' => [$procedure->getKey()],
            ])
            ->assertSessionHasErrors('patient_treatment_ids');

        $this->assertDatabaseCount('dental_invoices', 1);
        $this->assertDatabaseCount('dental_invoice_details', 1);
    }

    public function test_partial_full_duplicate_and_overpayments_keep_balance_consistent(): void
    {
        $administrator = $this->userWithRole(Role::ADMINISTRATOR);
        $patient = Patient::factory()->create();
        $invoice = $this->issueInvoice($administrator, $patient, [$this->procedure($patient, '100.00', 'Consulta')]);
        $firstKey = (string) Str::uuid();

        $this->actingAs($administrator)
            ->post(route('facturacion.pagos.store', $invoice), $this->paymentData('100.01', (string) Str::uuid()))
            ->assertSessionHasErrors('amount');
        $this->actingAs($administrator)
            ->post(route('facturacion.pagos.store', $invoice), $this->paymentData('0.00', (string) Str::uuid()))
            ->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('dental_payments', 0);
        $this->assertSame('100.00', $invoice->fresh()->balance_due);

        $this->actingAs($administrator)
            ->post(route('facturacion.pagos.store', $invoice), $this->paymentData('35.00', $firstKey))
            ->assertRedirect();

        $payment = DentalPayment::query()->firstOrFail();
        $this->assertSame(DentalInvoiceStatus::PartiallyPaid, $invoice->fresh()->status);
        $this->assertSame('65.00', $invoice->fresh()->balance_due);

        $this->actingAs($administrator)
            ->post(route('facturacion.pagos.store', $invoice), $this->paymentData('35.00', $firstKey))
            ->assertRedirectToRoute('facturacion.pagos.show', $payment);
        $this->assertDatabaseCount('dental_payments', 1);
        $this->assertSame('65.00', $invoice->fresh()->balance_due);

        $this->actingAs($administrator)
            ->post(route('facturacion.pagos.store', $invoice), $this->paymentData('65.00', (string) Str::uuid()))
            ->assertRedirect();
        $this->assertSame(DentalInvoiceStatus::Paid, $invoice->fresh()->status);
        $this->assertSame('0.00', $invoice->fresh()->balance_due);
        $this->assertDatabaseCount('dental_payments', 2);

        $this->actingAs($administrator)
            ->post(route('facturacion.pagos.store', $invoice), $this->paymentData('0.01', (string) Str::uuid()))
            ->assertSessionHasErrors('invoice');
        $this->assertDatabaseCount('dental_payments', 2);
    }

    public function test_payment_corrections_restore_balance_and_financial_documents_are_not_deleted(): void
    {
        $administrator = $this->userWithRole(Role::ADMINISTRATOR);
        $patient = Patient::factory()->create();
        $invoice = $this->issueInvoice($administrator, $patient, [$this->procedure($patient, '80.00', 'Radiografía')]);

        $this->actingAs($administrator)
            ->post(route('facturacion.pagos.store', $invoice), $this->paymentData('20.00', (string) Str::uuid()))
            ->assertRedirect();
        $payment = DentalPayment::query()->firstOrFail();

        $this->actingAs($administrator)
            ->post(route('facturacion.facturas.cancel', $invoice), ['reason' => 'Factura con pago activo'])
            ->assertSessionHasErrors('invoice');
        $this->assertSame(DentalInvoiceStatus::PartiallyPaid, $invoice->fresh()->status);

        $this->actingAs($administrator)
            ->post(route('facturacion.pagos.cancel', $payment), ['reason' => 'Referencia capturada incorrectamente'])
            ->assertRedirectToRoute('facturacion.pagos.show', $payment);

        $payment->refresh();
        $invoice->refresh();
        $this->assertSame(DentalPaymentStatus::Cancelled, $payment->status);
        $this->assertSame('Referencia capturada incorrectamente', $payment->cancellation_reason);
        $this->assertSame(DentalInvoiceStatus::Issued, $invoice->status);
        $this->assertSame('80.00', $invoice->balance_due);

        $this->actingAs($administrator)
            ->post(route('facturacion.facturas.cancel', $invoice), ['reason' => 'Procedimiento duplicado'])
            ->assertRedirectToRoute('facturacion.facturas.show', $invoice);

        $invoice->refresh();
        $this->assertSame(DentalInvoiceStatus::Cancelled, $invoice->status);
        $this->assertSame('0.00', $invoice->balance_due);
        $this->assertDatabaseHas('dental_invoices', ['id' => $invoice->getKey()]);
        $this->assertDatabaseHas('dental_payments', ['id' => $payment->getKey(), 'status' => DentalPaymentStatus::Cancelled->value]);

        $this->actingAs($administrator)
            ->post(route('facturacion.pagos.store', $invoice), $this->paymentData('1.00', (string) Str::uuid()))
            ->assertSessionHasErrors('invoice');
    }

    public function test_receptionist_can_issue_and_collect_but_cannot_cancel_documents(): void
    {
        $receptionist = $this->userWithRole(Role::RECEPTIONIST);
        $patient = Patient::factory()->create();
        $invoice = $this->issueInvoice($receptionist, $patient, [$this->procedure($patient, '60.00', 'Consulta inicial')]);

        $this->actingAs($receptionist)
            ->post(route('facturacion.facturas.cancel', $invoice), ['reason' => 'Solicitud de cancelación'])
            ->assertForbidden();

        $this->actingAs($receptionist)
            ->post(route('facturacion.pagos.store', $invoice), $this->paymentData('10.00', (string) Str::uuid()))
            ->assertRedirect();

        $this->assertSame(DentalInvoiceStatus::PartiallyPaid, $invoice->fresh()->status);
        $this->assertDatabaseCount('dental_payments', 1);
    }

    public function test_dentist_can_only_see_financial_amounts_for_their_own_treatments(): void
    {
        $administrator = $this->userWithRole(Role::ADMINISTRATOR);
        $dentistUser = $this->userWithRole(Role::DENTIST);
        $patient = Patient::factory()->create();
        $assignedDentist = Dentist::factory()->for($dentistUser)->create();
        $patient->dentists()->attach($assignedDentist);

        $ownProcedure = $this->procedure($patient, '40.00', 'Resina asignada', null, $assignedDentist);
        $otherProcedure = $this->procedure($patient, '75.00', 'Corona de otro odontólogo');
        $invoice = $this->issueInvoice($administrator, $patient, [$ownProcedure, $otherProcedure]);

        $this->actingAs($dentistUser)
            ->get(route('pacientes.resumen-financiero', $patient))
            ->assertOk()
            ->assertSeeText('Resina asignada')
            ->assertDontSeeText('Corona de otro odontólogo')
            ->assertDontSeeText('NOTA CLINICA PRIVADA')
            ->assertDontSeeText('75.00');

        $this->actingAs($dentistUser)
            ->get(route('facturacion.facturas.index'))
            ->assertForbidden();

        $this->actingAs($dentistUser)
            ->get(route('facturacion.facturas.show', $invoice))
            ->assertForbidden();

        $this->actingAs($dentistUser)
            ->post(route('facturacion.pagos.store', $invoice), $this->paymentData('1.00', (string) Str::uuid()))
            ->assertForbidden();
    }

    public function test_printable_documents_omit_clinical_notes_and_reports_show_financial_totals(): void
    {
        $administrator = $this->userWithRole(Role::ADMINISTRATOR);
        $patient = Patient::factory()->create();
        $invoice = $this->issueInvoice(
            $administrator,
            $patient,
            [$this->procedure($patient, '125.00', 'Endodoncia', 'NOTA CLINICA PRIVADA')],
        );

        $this->actingAs($administrator)
            ->get(route('facturacion.facturas.document', $invoice))
            ->assertOk()
            ->assertSeeText('Comprobante administrativo interno')
            ->assertSeeText('Endodoncia')
            ->assertDontSeeText('NOTA CLINICA PRIVADA');

        $this->actingAs($administrator)
            ->post(route('facturacion.pagos.store', $invoice), $this->paymentData('25.00', (string) Str::uuid()))
            ->assertRedirect();
        $payment = DentalPayment::query()->firstOrFail();
        $this->actingAs($administrator)
            ->get(route('facturacion.pagos.receipt', $payment))
            ->assertOk()
            ->assertSeeText('Recibo administrativo interno')
            ->assertSeeText($payment->number)
            ->assertDontSeeText('NOTA CLINICA PRIVADA');

        $this->actingAs($administrator)
            ->get(route('facturacion.reportes.index', ['from' => today()->toDateString(), 'to' => today()->toDateString()]))
            ->assertOk()
            ->assertSeeText('Q 125.00')
            ->assertSeeText('Q 25.00')
            ->assertSeeText('Q 100.00');
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::query()->firstOrCreate(['name' => $roleName]);

        return User::factory()->for($role)->create(['is_active' => true]);
    }

    private function procedure(
        Patient $patient,
        string $price,
        string $treatmentName,
        ?string $clinicalNotes = 'NOTA CLINICA PRIVADA',
        ?Dentist $dentist = null,
    ): PatientTreatment {
        $treatment = Treatment::factory()->create([
            'name' => $treatmentName,
            'price' => $price,
        ]);

        return PatientTreatment::factory()
            ->for($patient)
            ->for($dentist ?? Dentist::factory()->create())
            ->for($treatment)
            ->create([
                'price_applied' => $price,
                'procedure_description' => $treatmentName,
                'clinical_notes' => $clinicalNotes,
            ]);
    }

    /** @param list<PatientTreatment> $procedures */
    private function createInvoiceDraft(User $user, Patient $patient, array $procedures): DentalInvoice
    {
        $this->actingAs($user)
            ->post(route('facturacion.facturas.store'), [
                'patient_id' => $patient->getKey(),
                'patient_treatment_ids' => array_map(
                    fn (PatientTreatment $procedure): int => $procedure->getKey(),
                    $procedures,
                ),
            ])
            ->assertRedirect();

        return DentalInvoice::query()->latest('id')->firstOrFail();
    }

    /** @param list<PatientTreatment> $procedures */
    private function issueInvoice(User $user, Patient $patient, array $procedures): DentalInvoice
    {
        $invoice = $this->createInvoiceDraft($user, $patient, $procedures);

        $this->actingAs($user)
            ->post(route('facturacion.facturas.issue', $invoice))
            ->assertRedirect();

        return $invoice->fresh();
    }

    /** @return array{amount: string, method: string, payment_date: string, idempotency_key: string} */
    private function paymentData(string $amount, string $idempotencyKey): array
    {
        return [
            'amount' => $amount,
            'method' => 'cash',
            'payment_date' => today()->toDateString(),
            'idempotency_key' => $idempotencyKey,
        ];
    }
}
