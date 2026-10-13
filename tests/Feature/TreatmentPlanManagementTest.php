<?php

namespace Tests\Feature;

use App\Enums\TreatmentPlanStatus;
use App\Models\Dentist;
use App\Models\Patient;
use App\Models\Role;
use App\Models\Treatment;
use App\Models\TreatmentPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class TreatmentPlanManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_assigned_dentist_can_create_a_plan_with_estimated_prices(): void
    {
        [$dentistUser, , $patient] = $this->assignedContext();
        $exam = Treatment::factory()->create(['name' => 'Evaluación', 'price' => '180.00']);
        $cleaning = Treatment::factory()->create(['name' => 'Limpieza', 'price' => '300.00']);

        $this->actingAs($dentistUser)
            ->post(route('pacientes.planes.store', $patient), [
                'title' => 'Plan preventivo',
                'description' => 'Atención preventiva semestral.',
                'items' => [
                    ['treatment_id' => $exam->id, 'quantity' => 1, 'estimated_unit_price' => '200.00'],
                    ['treatment_id' => $cleaning->id, 'quantity' => 2, 'estimated_unit_price' => null],
                ],
            ])
            ->assertRedirect();

        $plan = TreatmentPlan::query()->with('items')->firstOrFail();
        $this->actingAs($dentistUser)
            ->get(route('pacientes.planes.show', [$patient, $plan]))
            ->assertOk()
            ->assertSeeText('Plan preventivo')
            ->assertSeeText('Limpieza');

        $this->actingAs($dentistUser)
            ->get(route('pacientes.planes.index', $patient))
            ->assertOk()
            ->assertSeeText('Plan preventivo');

        $this->assertDatabaseHas('treatment_plans', [
            'id' => $plan->id,
            'patient_id' => $patient->id,
            'dentist_id' => $dentistUser->dentist->id,
            'created_by' => $dentistUser->id,
            'status' => TreatmentPlanStatus::Pending->value,
        ]);
        $this->assertDatabaseHas('treatment_plan_items', [
            'treatment_plan_id' => $plan->id,
            'treatment_id' => $exam->id,
            'quantity' => 1,
            'estimated_unit_price' => '200.00',
        ]);
        $this->assertDatabaseHas('treatment_plan_items', [
            'treatment_plan_id' => $plan->id,
            'treatment_id' => $cleaning->id,
            'quantity' => 2,
            'estimated_unit_price' => '300.00',
        ]);
    }

    public function test_plan_cannot_be_completed_before_all_items_have_procedures(): void
    {
        [$dentistUser, , $patient] = $this->assignedContext();
        $treatment = Treatment::factory()->create();
        $plan = TreatmentPlan::factory()->for($patient)->for($dentistUser->dentist)->create([
            'status' => TreatmentPlanStatus::InProgress,
        ]);
        $planItem = $plan->items()->create([
            'treatment_id' => $treatment->id,
            'quantity' => 1,
            'estimated_unit_price' => $treatment->price,
            'status' => TreatmentPlanStatus::Pending,
        ]);

        $this->actingAs($dentistUser)
            ->patch(route('pacientes.planes.update', [$patient, $plan]), ['status' => TreatmentPlanStatus::Completed->value])
            ->assertSessionHasErrors([
                'status' => 'Completa los procedimientos asociados antes de cerrar el plan.',
            ]);

        $this->assertSame(TreatmentPlanStatus::InProgress, $plan->fresh()->status);
        $this->assertSame(TreatmentPlanStatus::Pending, $planItem->fresh()->status);
    }

    public function test_item_progress_updates_the_parent_plan_without_marking_a_procedure_complete(): void
    {
        [$dentistUser, $dentist, $patient] = $this->assignedContext();
        $treatment = Treatment::factory()->create();
        $plan = TreatmentPlan::factory()->for($patient)->for($dentist)->create();
        $item = $plan->items()->create([
            'treatment_id' => $treatment->id,
            'quantity' => 1,
            'estimated_unit_price' => $treatment->price,
            'status' => TreatmentPlanStatus::Pending,
        ]);

        $this->actingAs($dentistUser)
            ->patch(route('pacientes.planes.items.update', [$patient, $plan, $item]), [
                'status' => TreatmentPlanStatus::InProgress->value,
            ])
            ->assertRedirect();

        $this->assertSame(TreatmentPlanStatus::InProgress, $plan->fresh()->status);
        $this->assertSame(TreatmentPlanStatus::InProgress, $item->fresh()->status);
        $this->assertDatabaseCount('patient_treatments', 0);
    }

    public function test_cancelling_a_plan_cancels_open_items_and_removes_them_from_pending_history(): void
    {
        [$dentistUser, $dentist, $patient] = $this->assignedContext();
        $treatment = Treatment::factory()->create(['name' => 'Tratamiento pendiente cancelado']);
        $plan = TreatmentPlan::factory()->for($patient)->for($dentist)->create([
            'status' => TreatmentPlanStatus::InProgress,
        ]);
        $item = $plan->items()->create([
            'treatment_id' => $treatment->id,
            'quantity' => 1,
            'estimated_unit_price' => $treatment->price,
            'status' => TreatmentPlanStatus::InProgress,
        ]);

        $this->actingAs($dentistUser)
            ->patch(route('pacientes.planes.update', [$patient, $plan]), [
                'status' => TreatmentPlanStatus::Cancelled->value,
            ])
            ->assertRedirect();

        $this->assertSame(TreatmentPlanStatus::Cancelled, $plan->fresh()->status);
        $this->assertSame(TreatmentPlanStatus::Cancelled, $item->fresh()->status);
        $this->actingAs($dentistUser)
            ->get(route('pacientes.historial-clinico', $patient))
            ->assertOk()
            ->assertViewHas('pendingPlanItems', fn ($items): bool => $items->isEmpty());
    }

    private function assignedContext(): array
    {
        $role = Role::factory()->create(['name' => Role::DENTIST]);
        $dentistUser = User::factory()->for($role)->create(['is_active' => true]);
        $dentist = Dentist::factory()->for($dentistUser, 'user')->create();
        $patient = Patient::factory()->create();
        $patient->dentists()->attach($dentist);

        return [$dentistUser, $dentist, $patient];
    }
}
