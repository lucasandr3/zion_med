<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OrganizationResource;
use App\Models\Clinic;
use App\Services\OrganizationAccessService;
use App\Services\OrganizationPresenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ChooseClinicController extends Controller
{
    public function __construct(
        private readonly OrganizationAccessService $organizationAccess,
    ) {}
    /**
     * Lista organizações que o usuário pode escolher (para trocar contexto).
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorizeClinicSwitch($request);

        $user = $request->user();
        $organizations = $this->organizationAccess->allowedOrganizationsForUser($user);
        $currentOrganizationId = session('current_organization_id') ?? session('current_clinic_id');

        return response()->json([
            'data' => [
                'organizations' => OrganizationResource::collection($organizations),
                'current_organization_id' => $currentOrganizationId,
            ],
        ]);
    }

    /**
     * Define a organização atual. Nas próximas requisições, envie o header X-Organization-Id com o id escolhido.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorizeClinicSwitch($request);

        $user = $request->user();
        $allowedIds = $this->organizationAccess->allowedOrganizationIdsForUser($user);

        if (empty($allowedIds)) {
            return response()->json(['message' => 'Nenhuma empresa disponível para seleção.'], 403);
        }

        $validated = $request->validate([
            'organization_id' => ['required_without:clinic_id', 'integer', Rule::in($allowedIds)],
            'clinic_id' => ['required_without:organization_id', 'integer', Rule::in($allowedIds)],
        ]);

        $organizationId = (int) ($validated['organization_id'] ?? $validated['clinic_id']);

        $oldOrganizationId = session('current_organization_id') ?? session('current_clinic_id');
        $oldOrganizationId = $oldOrganizationId !== null && $oldOrganizationId !== '' ? (int) $oldOrganizationId : null;

        session([
            'current_clinic_id' => $organizationId,
            'current_organization_id' => $organizationId,
        ]);

        $presence = app(OrganizationPresenceService::class);
        if ($user->isTenantUser()) {
            if ($oldOrganizationId && $oldOrganizationId !== $organizationId && $presence->userMayAccessOrganization($user, $oldOrganizationId)) {
                $presence->leave($oldOrganizationId);
            }
            if (($oldOrganizationId === null || $oldOrganizationId !== $organizationId) && $presence->userMayAccessOrganization($user, $organizationId)) {
                $presence->join($organizationId);
            }
        }

        return response()->json([
            'data' => [
                'message' => 'Empresa alterada. Use o header X-Organization-Id nas próximas requisições com o valor '.$organizationId,
                'current_organization_id' => $organizationId,
            ],
        ]);
    }

    private function authorizeClinicSwitch(Request $request): void
    {
        $user = $request->user();
        if (! $user) {
            abort(403);
        }
        if ($user->canSwitchClinic()) {
            return;
        }
        $clinic = $user->clinic;
        if ($clinic?->tenant_id && Clinic::withoutGlobalScopes()->where('tenant_id', $clinic->tenant_id)->count() > 1) {
            return;
        }
        abort(403);
    }

}
