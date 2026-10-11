<?php

namespace Tests\Feature;

use App\Models\Employee;
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

    public function test_authenticated_user_can_view_dashboard_totals(): void
    {
        $authenticatedUser = User::factory()->create(['is_active' => true]);
        User::factory()->create(['is_active' => false]);
        Employee::factory()->create(['is_active' => true]);
        Employee::factory()->create(['is_active' => false]);

        $this->actingAs($authenticatedUser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewIs('dashboard')
            ->assertViewHasAll([
                'totalUsers' => 2,
                'activeUsers' => 1,
                'totalEmployees' => 2,
                'activeEmployees' => 1,
            ]);
    }
}
