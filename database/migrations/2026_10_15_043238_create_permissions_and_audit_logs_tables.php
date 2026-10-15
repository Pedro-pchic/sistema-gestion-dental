<?php

use App\Models\Role;
use App\Services\PermissionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('module');
            $table->string('label');
        });

        Schema::table('roles', function (Blueprint $table): void {
            $table->boolean('permissions_initialized')->default(false);
        });

        Schema::create('role_permissions', function (Blueprint $table): void {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('actor_role', 80);
            $table->string('module', 80);
            $table->string('action', 80);
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->string('result', 16);
            $table->jsonb('metadata')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('request_id', 100)->nullable();
            $table->timestampTz('created_at')->useCurrent()->index();
            $table->index(['module', 'created_at']);
            $table->index(['actor_user_id', 'created_at']);
        });

        foreach (PermissionCatalog::GROUPS as $module => $permissions) {
            foreach ($permissions as $name => $label) {
                DB::table('permissions')->insert(['name' => $name, 'module' => $module, 'label' => $label]);
            }
        }

        $permissionIds = DB::table('permissions')->pluck('id', 'name');
        foreach (DB::table('roles')->whereIn('name', Role::INITIAL_ROLES)->get(['id', 'name']) as $role) {
            foreach (PermissionCatalog::defaults($role->name) as $name) {
                DB::table('role_permissions')->insert(['role_id' => $role->id, 'permission_id' => $permissionIds[$name]]);
            }
            DB::table('roles')->where('id', $role->id)->update(['permissions_initialized' => true]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('role_permissions');
        Schema::table('roles', function (Blueprint $table): void {
            $table->dropColumn('permissions_initialized');
        });
        Schema::dropIfExists('permissions');
    }
};
