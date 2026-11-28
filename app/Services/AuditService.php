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
        $attributes = [
            'user_id' => $actor?->id,
            'action' => $action,
            'entity_type' => class_basename($entity),
            'entity_id' => $entity->getKey(),
            'changes' => $changes ?: null,
            'ip_address' => request()?->ip(),
        ];

        // Pengguna virtual (Mode Viewer) tidak ada di tabel users: jangan menulis apa pun ke database.
        if ($actor !== null && ! $actor->exists) {
            return new AuditLog($attributes);
        }

        return AuditLog::create($attributes);
    }
}
