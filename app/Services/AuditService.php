<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    /**
     * Record WHO did WHAT to WHICH entity and WHEN (created_at).
     * Never pass passwords or secrets in $changes.
     */
    public function log(?User $actor, string $action, Model $entity, array $changes = []): AuditLog
    {
        return AuditLog::create([
            'user_id' => $actor?->id,
            'action' => $action,
            'entity_type' => class_basename($entity),
            'entity_id' => $entity->getKey(),
            'changes' => $changes ?: null,
            'ip_address' => request()?->ip(),
        ]);
    }
}
