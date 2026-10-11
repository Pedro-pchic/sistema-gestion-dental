<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

#[Signature('dental:admin:create {--email=} {--name=}')]
#[Description('Create the first administrator for the dental system')]
class CreateInitialAdminCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = trim((string) ($this->option('email') ?: config('seeders.initial_admin.email')));
        $name = trim((string) ($this->option('name') ?: config('seeders.initial_admin.name')));
        $password = (string) config('seeders.initial_admin.password', '');

        if ($this->input->isInteractive()) {
            $email = $email !== '' ? $email : trim((string) $this->ask('Correo del administrador'));
            $name = $name !== '' ? $name : trim((string) $this->ask('Nombre del administrador'));

            if ($password === '') {
                $password = (string) $this->secret('Contraseña inicial (mínimo 12 caracteres)');
                $confirmation = (string) $this->secret('Confirma la contraseña');

                if (! hash_equals($password, $confirmation)) {
                    $this->components->error('Las contraseñas no coinciden.');

                    return self::FAILURE;
                }
            }
        }

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->components->error('Configura un correo válido con DENTAL_ADMIN_EMAIL o usa --email.');

            return self::FAILURE;
        }

        if ($name === '') {
            $this->components->error('Configura DENTAL_ADMIN_NAME o usa --name.');

            return self::FAILURE;
        }

        if (mb_strlen($password) < 12) {
            $this->components->error('La contraseña inicial debe tener al menos 12 caracteres.');

            return self::FAILURE;
        }

        $administratorRole = Role::firstOrCreate(['name' => Role::ADMINISTRATOR]);

        $created = DB::transaction(function () use ($administratorRole, $email, $name, $password): bool {
            $lockedRole = Role::query()->lockForUpdate()->findOrFail($administratorRole->id);

            if ($lockedRole->users()->exists() || User::query()->where('email', $email)->exists()) {
                return false;
            }

            User::create([
                'name' => $name,
                'email' => $email,
                'role_id' => $lockedRole->id,
                'is_active' => true,
                'password' => Hash::make($password),
            ]);

            return true;
        });

        if (! $created) {
            $this->components->error('Ya existe una cuenta administradora o el correo ya está registrado.');

            return self::FAILURE;
        }

        $this->components->info('Cuenta administradora creada correctamente.');

        return self::SUCCESS;
    }
}
