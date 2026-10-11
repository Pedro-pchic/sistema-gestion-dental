<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateInitialAdminCommandTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_command_creates_the_first_administrator_with_a_hashed_password(): void
    {
        Config::set('seeders.initial_admin', [
            'name' => 'Administradora Dental',
            'email' => 'admin@dental.test',
            'password' => 'UnaClaveSegura!2026',
        ]);

        $this->artisan('dental:admin:create', ['--no-interaction' => true])
            ->assertExitCode(0);

        $administrator = User::query()->where('email', 'admin@dental.test')->firstOrFail();

        $this->assertSame('Administradora Dental', $administrator->name);
        $this->assertTrue($administrator->hasRole(Role::ADMINISTRATOR));
        $this->assertTrue(Hash::check('UnaClaveSegura!2026', $administrator->password));
        $this->assertNotSame('UnaClaveSegura!2026', $administrator->password);

        $this->artisan('dental:admin:create', [
            '--email' => 'second-admin@dental.test',
            '--name' => 'Segunda Administradora',
            '--no-interaction' => true,
        ])->assertExitCode(1);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_command_refuses_to_create_an_administrator_without_a_configured_password(): void
    {
        Config::set('seeders.initial_admin', [
            'name' => 'Administradora Dental',
            'email' => 'admin@dental.test',
            'password' => '',
        ]);

        $this->artisan('dental:admin:create', ['--no-interaction' => true])
            ->assertExitCode(1);

        $this->assertDatabaseCount('users', 0);
    }
}
