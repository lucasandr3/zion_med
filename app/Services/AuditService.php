<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

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
        $clinicId = $clinicId ?? Auth::user()?->clinic_id ?? session('current_clinic_id');
        $userId = $userId ?? Auth::id();

        return AuditLog::create([
            'clinic_id' => $clinicId,
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'meta_json' => $meta,
            'created_at' => now(),
        ]);
    }
}
