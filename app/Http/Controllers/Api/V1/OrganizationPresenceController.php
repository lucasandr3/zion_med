<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OrganizationPresenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class OrganizationPresenceController extends Controller
{
    /**
     * Fechamento de aba/navegador (sendBeacon não envia Authorization).
     * Corpo: token (Bearer plain) + organization_id.
     */
    public function leaveBeacon(Request $request, OrganizationPresenceService $service): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'organization_id' => ['required', 'integer', 'min:1'],
        ]);

        $plainToken = $request->string('token')->toString();
        $accessToken = PersonalAccessToken::findToken($plainToken);
        if (! $accessToken || ! $accessToken->tokenable instanceof User) {
            return response()->json(['data' => ['ok' => false]], 401);
        }

        /** @var User $user */
        $user = $accessToken->tokenable;
        $organizationId = (int) $request->input('organization_id');

        if (! $user->isTenantUser() || ! $service->userMayAccessOrganization($user, $organizationId)) {
            return response()->json(['data' => ['ok' => false]], 403);
        }

        $service->leave($organizationId);

        return response()->json(['data' => ['ok' => true]]);
    }
}
