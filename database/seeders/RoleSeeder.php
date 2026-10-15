<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Services\PermissionCatalog;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (Role::INITIAL_ROLES as $name) {
            $role = Role::firstOrCreate(['name' => $name]);
            if (! $role->permissions_initialized) {
                $role->permissions()->sync(Permission::query()->whereIn('name', PermissionCatalog::defaults($name))->pluck('id')->all());
                $role->permissions_initialized = true;
                $role->save();
            }
        }
    }
}
