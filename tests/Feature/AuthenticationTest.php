<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_active_user_can_log_in_and_log_out(): void
    {
        $user = User::factory()->create([
            'email' => 'active@example.com',
            'is_active' => true,
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirectToRoute('dashboard');

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('audit_logs', ['module' => 'security', 'action' => 'login', 'result' => 'success', 'actor_user_id' => $user->id]);

        $this->post(route('logout'))->assertRedirectToRoute('login');

        $this->assertDatabaseHas('audit_logs', ['module' => 'security', 'action' => 'logout', 'result' => 'success', 'actor_user_id' => $user->id]);

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $user = User::factory()->create([
            'email' => 'inactive@example.com',
            'is_active' => false,
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
        $rejected = AuditLog::query()->where('action', 'login')->where('result', 'rejected')->firstOrFail();
        $this->assertNull($rejected->actor_user_id);
        $this->assertSame([], $rejected->metadata);
    }

    public function test_authenticated_inactive_user_is_logged_out_from_protected_routes(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirectToRoute('login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
