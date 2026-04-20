<?php

namespace App\Http\Controllers\Api\V1\Platform;

use App\Http\Controllers\Controller;
use App\Models\OrganizationPresence;
use Illuminate\Http\JsonResponse;

class OrganizationPresenceController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $rows = OrganizationPresence::query()
            ->orderBy('organization_name')
            ->get();

        return response()->json([
            'data' => $rows->map(fn (OrganizationPresence $r) => [
                'organization_id' => (int) $r->organization_id,
                'organization_name' => (string) $r->organization_name,
                'active_sessions' => (int) $r->active_sessions,
                'last_seen_at' => $r->last_seen_at?->toIso8601String(),
            ]),
        ]);
    }
}
