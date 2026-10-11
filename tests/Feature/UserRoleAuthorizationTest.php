<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class UserRoleAuthorizationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_user_without_administrator_role_cannot_manage_users(): void
    {
        $role = Role::factory()->create(['name' => Role::HUMAN_RESOURCES]);
        $user = User::factory()->for($role)->create(['is_active' => true]);

        $this->actingAs($user)
            ->get(route('usuarios.index'))
            ->assertForbidden();
    }
}
