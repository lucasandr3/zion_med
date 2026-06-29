<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OrganizationResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    /**
     * Retorna o usuário autenticado e a organização atual do contexto.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $organizationId = session('current_organization_id') ?? session('current_clinic_id');
        $organization = $organizationId ? Organization::query()->find($organizationId) : null;
        if ($organization) {
            $organization->refresh();
        }

        $trialNotice = $organization?->trialEndingNoticeMeta();

        return response()->json([
            'data' => [
                'user' => new UserResource($user),
                'organization' => $organization ? new OrganizationResource($organization) : null,
                'trial_notice' => $trialNotice,
            ],
        ]);
    }
}
