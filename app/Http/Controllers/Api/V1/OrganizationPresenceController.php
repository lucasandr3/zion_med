<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OrganizationPresenceService;
use App\Support\PresenceLeaveToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationPresenceController extends Controller
{
    /**
     * Fechamento de aba/navegador (sendBeacon não envia Authorization).
     * Corpo: presence_leave_token (HMAC de curta duração) + organization_id.
     * Aceita `token` legado apenas se ainda for um leave token (não Sanctum).
     */
    public function leaveBeacon(Request $request, OrganizationPresenceService $service): JsonResponse
    {
        $request->validate([
            'presence_leave_token' => ['nullable', 'string'],
            'token' => ['nullable', 'string'],
            'organization_id' => ['required', 'integer', 'min:1'],
        ]);

        $leaveToken = (string) ($request->input('presence_leave_token') ?: $request->input('token') ?: '');
        if ($leaveToken === '') {
            return response()->json(['data' => ['ok' => false]], 401);
        }

        $parsed = PresenceLeaveToken::parse($leaveToken);
        if (! $parsed) {
            return response()->json(['data' => ['ok' => false]], 401);
        }

        $organizationId = (int) $request->input('organization_id');
        if ($parsed['organization_id'] !== $organizationId) {
            return response()->json(['data' => ['ok' => false]], 403);
        }

        $user = User::query()->find($parsed['user_id']);
        if (! $user || ! $user->active || ! $user->isTenantUser()) {
            return response()->json(['data' => ['ok' => false]], 403);
        }

        if (! $service->userMayAccessOrganization($user, $organizationId)) {
            return response()->json(['data' => ['ok' => false]], 403);
        }

        $service->leave($organizationId);

        return response()->json(['data' => ['ok' => true]]);
    }
}
