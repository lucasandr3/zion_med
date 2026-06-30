<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Support\OrganizationContext;

class AuditService
{
    public function log(
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $meta = null,
        ?int $clinicId = null,
        ?int $userId = null
    ): AuditLog {
        $organizationId = $clinicId ?? OrganizationContext::id();
        $userId = $userId ?? \Illuminate\Support\Facades\Auth::id();

        return AuditLog::create([
            'organization_id' => $organizationId,
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'meta_json' => $meta,
            'created_at' => now(),
        ]);
    }
}
