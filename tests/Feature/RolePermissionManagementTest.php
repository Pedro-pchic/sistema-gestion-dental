<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class RolePermissionManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_administrator_can_list_and_revoke_a_permission_without_bypassing_policies(): void
    {
        $admin = User::factory()->for(Role::factory()->create(['name' => Role::ADMINISTRATOR]))->create(['is_active' => true]);
        $receptionRole = Role::factory()->create(['name' => Role::RECEPTIONIST]);
        $receptionist = User::factory()->for($receptionRole)->create(['is_active' => true]);
        $selected = array_values(array_diff($receptionRole->effectivePermissionNames(), ['appointments.create']));

        $this->actingAs($admin)->get(route('roles.index'))->assertOk()->assertSeeText('Recepcionista');
        $this->actingAs($admin)->get(route('roles.edit', $receptionRole))->assertOk()->assertSeeText('appointments.create');
        $this->assertTrue(Gate::forUser($receptionist)->allows('create', Appointment::class));

        $this->actingAs($admin)->put(route('roles.update', $receptionRole), [
            'permissions' => $selected,
            'confirm_changes' => '1',
        ])->assertRedirectToRoute('roles.edit', $receptionRole);

        $this->assertDatabaseMissing('role_permissions', ['role_id' => $receptionRole->id, 'permission_id' => Permission::where('name', 'appointments.create')->value('id')]);
        $this->assertFalse(Gate::forUser($receptionist->fresh())->allows('create', Appointment::class));
        $log = AuditLog::where('action', 'role_permissions_changed')->latest('id')->firstOrFail();
        $this->assertSame($admin->id, $log->actor_user_id);
        $this->assertSame(['appointments.create'], $log->metadata['revoked']);

        $this->actingAs($admin)->put(route('roles.update', $receptionRole), [
            'permissions' => $selected === [] ? ['appointments.create'] : [...$selected, 'appointments.create'],
            'confirm_changes' => '1',
        ])->assertRedirectToRoute('roles.edit', $receptionRole);

        $this->assertTrue(Gate::forUser($receptionist->fresh())->allows('create', Appointment::class));
    }

    public function test_only_administrators_can_manage_roles_and_critical_permissions_cannot_be_revoked_or_escalated(): void
    {
        $adminRole = Role::factory()->create(['name' => Role::ADMINISTRATOR]);
        $admin = User::factory()->for($adminRole)->create(['is_active' => true]);
        $dentistRole = Role::factory()->create(['name' => Role::DENTIST]);
        $dentist = User::factory()->for($dentistRole)->create(['is_active' => true]);

        $this->actingAs($dentist)->get(route('roles.index'))->assertForbidden();
        $this->actingAs($dentist)->put(route('roles.update', $dentistRole), ['permissions' => ['audit.view'], 'confirm_changes' => '1'])->assertForbidden();
        $this->assertDatabaseHas('audit_logs', ['module' => 'security', 'action' => 'access_denied', 'result' => 'rejected', 'actor_user_id' => $dentist->id]);

        $this->actingAs($admin)->put(route('roles.update', $adminRole), [
            'permissions' => ['dashboard.view'],
            'confirm_changes' => '1',
        ])->assertSessionHasErrors('permissions');
        $this->assertTrue(Gate::forUser($admin->fresh())->allows('roles.manage'));

        $this->actingAs($admin)->put(route('roles.update', $dentistRole), [
            'permissions' => ['clinical_records.view', 'audit.view'],
            'confirm_changes' => '1',
        ])->assertSessionHasErrors('permissions');
        $this->assertFalse(Gate::forUser($dentist->fresh())->allows('audit.view'));
        $patient = Patient::factory()->create();
        $this->assertFalse(Gate::forUser($admin)->allows('viewClinicalHistory', $patient));
        $this->assertFalse(Gate::forUser($dentist)->allows('viewClinicalHistory', $patient));
    }

    public function test_permission_save_requires_confirmation_and_rejects_unknown_names(): void
    {
        $admin = User::factory()->for(Role::factory()->create(['name' => Role::ADMINISTRATOR]))->create(['is_active' => true]);
        $role = Role::factory()->create(['name' => Role::RECEPTIONIST]);

        $this->actingAs($admin)->put(route('roles.update', $role), ['permissions' => ['appointments.view']])->assertSessionHasErrors('confirm_changes');
        $this->actingAs($admin)->put(route('roles.update', $role), ['permissions' => ['unknown.permission'], 'confirm_changes' => '1'])->assertSessionHasErrors('permissions.0');
        $this->assertTrue(Gate::forUser(User::factory()->for($role)->create(['is_active' => true]))->allows('create', Appointment::class));

        $this->actingAs($admin)->put(route('roles.update', $role), ['confirm_changes' => '1'])->assertRedirectToRoute('roles.edit', $role);
        $this->assertSame([], $role->fresh()->effectivePermissionNames());
    }
}
