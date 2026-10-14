<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class UserAdministrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_administrator_can_list_users(): void
    {
        $administrator = $this->administrator();
        $managedUser = User::factory()->create(['name' => 'Usuario Gestionado']);

        $this->actingAs($administrator)
            ->get(route('usuarios.index'))
            ->assertOk()
            ->assertSeeText($managedUser->name);
    }

    public function test_administrator_can_create_user_with_role(): void
    {
        $administrator = $this->administrator();
        $role = Role::factory()->create(['name' => 'Ventas']);

        $this->actingAs($administrator)
            ->post(route('usuarios.store'), [
                'name' => 'Nueva Usuaria',
                'email' => 'nueva@example.com',
                'role_id' => $role->id,
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
                'is_active' => true,
            ])
            ->assertRedirectToRoute('usuarios.index');

        $user = User::where('email', 'nueva@example.com')->firstOrFail();

        $this->assertSame('Nueva Usuaria', $user->name);
        $this->assertSame($role->id, $user->role_id);
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check('secret123', $user->password));
    }

    public function test_administrator_can_edit_user(): void
    {
        $administrator = $this->administrator();
        $originalRole = Role::factory()->create(['name' => 'Bodega']);
        $newRole = Role::factory()->create(['name' => Role::PURCHASING]);
        $user = User::factory()->for($originalRole)->create();

        $this->actingAs($administrator)
            ->put(route('usuarios.update', $user), [
                'name' => 'Nombre Actualizado',
                'email' => 'actualizado@example.com',
                'role_id' => $newRole->id,
                'password' => null,
                'password_confirmation' => null,
                'is_active' => true,
            ])
            ->assertRedirectToRoute('usuarios.index');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nombre Actualizado',
            'email' => 'actualizado@example.com',
            'role_id' => $newRole->id,
        ]);
    }

    public function test_administrator_can_toggle_user_status_without_deleting_user(): void
    {
        $administrator = $this->administrator();
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($administrator)
            ->patch(route('usuarios.status', $user))
            ->assertRedirectToRoute('usuarios.index');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_active' => false,
        ]);
    }

    public function test_last_active_administrator_cannot_be_demoted_or_deactivated(): void
    {
        $administrator = $this->administrator();
        $dentistRole = Role::factory()->create(['name' => Role::DENTIST]);

        $this->actingAs($administrator)
            ->put(route('usuarios.update', $administrator), [
                'name' => $administrator->name,
                'email' => $administrator->email,
                'role_id' => $dentistRole->id,
                'is_active' => true,
            ])
            ->assertSessionHasErrors('is_active');

        $this->actingAs($administrator)
            ->patch(route('usuarios.status', $administrator))
            ->assertSessionHasErrors('is_active');

        $this->assertSame(Role::ADMINISTRATOR, $administrator->fresh()->role->name);
        $this->assertTrue($administrator->is_active);

        $this->actingAs($administrator)
            ->put(route('usuarios.update', $administrator), [
                'name' => 'Administradora Actualizada',
                'email' => $administrator->email,
                'role_id' => (string) $administrator->role_id,
                'is_active' => '1',
            ])
            ->assertRedirectToRoute('usuarios.index');

        $secondAdministrator = User::factory()->for($administrator->role)->create(['is_active' => true]);
        $this->actingAs($administrator)
            ->patch(route('usuarios.status', $secondAdministrator))
            ->assertRedirectToRoute('usuarios.index');

        $this->assertFalse($secondAdministrator->fresh()->is_active);
    }

    public function test_role_change_is_audited_without_personal_data_or_password(): void
    {
        $administrator = $this->administrator();
        $dentistRole = Role::factory()->create(['name' => Role::DENTIST]);
        $managedUser = User::factory()->create();
        Log::spy();

        $this->actingAs($administrator)
            ->put(route('usuarios.update', $managedUser), [
                'name' => $managedUser->name,
                'email' => $managedUser->email,
                'role_id' => $dentistRole->id,
                'is_active' => true,
            ])
            ->assertRedirectToRoute('usuarios.index');

        Log::shouldHaveReceived('info')
            ->withArgs(fn (string $message, array $context): bool => $message === 'audit.user.updated'
                && $context['actor_id'] === $administrator->id
                && $context['entity_id'] === $managedUser->id
                && $context['role_id_after'] === $dentistRole->id
                && ! array_key_exists('password', $context)
                && ! array_key_exists('email', $context))
            ->once();
    }

    private function administrator(): User
    {
        $role = Role::factory()->create(['name' => Role::ADMINISTRATOR]);

        return User::factory()->for($role)->create(['is_active' => true]);
    }
}
