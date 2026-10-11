<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DevelopmentAdminSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $email = config('seeders.initial_admin.email');
        $password = config('seeders.initial_admin.password');

        if (blank($email) || blank($password)) {
            $this->command?->warn('Configure DENTAL_ADMIN_EMAIL and DENTAL_ADMIN_PASSWORD locally or run dental:admin:create to create the administrator.');

            return;
        }

        $administratorRole = Role::where('name', Role::ADMINISTRATOR)->firstOrFail();

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => config('seeders.initial_admin.name'),
                'role_id' => $administratorRole->id,
                'is_active' => true,
                'password' => Hash::make($password),
            ],
        );
    }
}
