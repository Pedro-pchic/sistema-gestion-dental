<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['actor_user_id', 'actor_role', 'module', 'action', 'auditable_type', 'auditable_id', 'result', 'metadata', 'ip_address', 'request_id'])]
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    /** @param array<string, scalar|array<int, scalar>|null> $metadata */
    public static function record(string $module, string $action, ?Model $resource = null, string $result = 'success', array $metadata = [], ?User $actor = null): self
    {
        $actor ??= auth()->user();
        $metadata = array_intersect_key($metadata, array_flip(['changed_fields', 'role_id_before', 'role_id_after', 'is_active_before', 'is_active_after', 'status_before', 'status_after', 'added', 'revoked', 'dentist_ids_added', 'dentist_ids_revoked', 'status', 'total', 'amount', 'invoice_id', 'patient_id', 'appointment_id', 'route_name']));

        return self::query()->create([
            'actor_user_id' => $actor?->getKey(),
            'actor_role' => $actor?->role?->name ?? 'Sistema',
            'module' => $module,
            'action' => $action,
            'auditable_type' => $resource?->getMorphClass(),
            'auditable_id' => $resource?->getKey(),
            'result' => $result,
            'metadata' => $metadata,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            'request_id' => app()->runningInConsole() ? null : request()->attributes->get('request_id'),
        ]);
    }
}
