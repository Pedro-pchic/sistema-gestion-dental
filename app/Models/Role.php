<?php

namespace App\Models;

use App\Services\PermissionCatalog;
use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name'])]
class Role extends Model
{
    public const string ADMINISTRATOR = 'Administrador';

    public const string DENTIST = 'Dentista';

    public const string RECEPTIONIST = 'Recepcionista';

    /** @deprecated Legacy SIGEC role retained for isolated legacy tests. */
    public const string HUMAN_RESOURCES = 'Recursos Humanos';

    /** @deprecated Legacy SIGEC role retained for isolated legacy tests. */
    public const string PURCHASING = 'Compras';

    /** @deprecated Legacy SIGEC role retained for isolated legacy tests. */
    public const string SALES = 'Ventas';

    /** @deprecated Legacy SIGEC role retained for isolated legacy tests. */
    public const string WAREHOUSE = 'Bodega';

    /** @deprecated Legacy SIGEC role retained for isolated legacy tests. */
    public const string FINANCE = 'Finanzas';

    /** @deprecated Legacy SIGEC role retained for isolated legacy tests. */
    public const string MANAGER = 'Gerente';

    public const array INITIAL_ROLES = [
        self::ADMINISTRATOR,
        self::DENTIST,
        self::RECEPTIONIST,
    ];

    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    public function hasPermission(string $name): bool
    {
        if (! $this->permissions_initialized) {
            return in_array($name, PermissionCatalog::defaults($this->name), true);
        }

        return $this->permissions()->where('name', $name)->exists();
    }

    /** @return array<int, string> */
    public function effectivePermissionNames(): array
    {
        return $this->permissions_initialized
            ? $this->permissions->pluck('name')->all()
            : PermissionCatalog::defaults($this->name);
    }

    protected function casts(): array
    {
        return ['permissions_initialized' => 'boolean'];
    }
}
