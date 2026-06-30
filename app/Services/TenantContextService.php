<?php

namespace App\Services;

use App\Support\SanctumTenantAbility;
use Illuminate\Http\Request;

class TenantContextService
{
    public function __construct(
        private readonly OrganizationAccessService $organizationAccess,
    ) {}

    public function resolveOrganizationId(Request $request): ?int
    {
        $user = $request->user();
        if ($user === null) {
            return null;
        }

        $headerOrgId = $this->headerOrganizationId($request);
        $tokenOrgId = SanctumTenantAbility::fromToken($user->currentAccessToken());

        return $this->organizationAccess->resolveOrganizationIdForUser($user, $headerOrgId, $tokenOrgId);
    }

    /**
     * Define org no contexto da requisição (sessão + ability do token Sanctum).
     */
    public function applyOrganizationContext(Request $request, int $organizationId): void
    {
        $user = $request->user();
        if ($user !== null && ! $this->organizationAccess->userMayAccessOrganization($user, $organizationId)) {
            return;
        }

        session([
            'current_clinic_id' => $organizationId,
            'current_organization_id' => $organizationId,
        ]);

        if ($user !== null) {
            SanctumTenantAbility::applyToToken($user, $organizationId);
        }
    }

    /**
     * Sincroniza sessão e token a partir do header ou ability já presente no token.
     */
    public function establishFromRequest(Request $request): ?int
    {
        $organizationId = $this->resolveOrganizationId($request);
        if ($organizationId === null) {
            return null;
        }

        session([
            'current_clinic_id' => $organizationId,
            'current_organization_id' => $organizationId,
        ]);

        $user = $request->user();
        if ($user !== null) {
            $headerOrgId = $this->headerOrganizationId($request);
            if ($headerOrgId !== null && $headerOrgId === $organizationId) {
                SanctumTenantAbility::applyToToken($user, $organizationId);
            }
        }

        return $organizationId;
    }

    private function headerOrganizationId(Request $request): ?int
    {
        $headerOrgId = $request->header('X-Organization-Id') ?? $request->header('X-Clinic-Id');
        if ($headerOrgId === null || $headerOrgId === '') {
            return null;
        }

        return (int) $headerOrgId;
    }
}
