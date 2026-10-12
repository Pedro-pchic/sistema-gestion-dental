<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirectToRoute('login');
    }

    public function test_authenticated_user_without_a_dashboard_role_is_forbidden(): void
    {
        $authenticatedUser = User::factory()->create(['is_active' => true]);

        $this->actingAs($authenticatedUser)
            ->get(route('dashboard'))
            ->assertForbidden();
    }
}
