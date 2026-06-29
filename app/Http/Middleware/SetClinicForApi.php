<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Services\OrganizationAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetClinicForApi
{
    public function __construct(
        private readonly OrganizationAccessService $organizationAccess,
    ) {}

    /**
     * Define a organização do contexto para requisições API (Sanctum).
     * Usa organization_id do usuário; header X-Organization-Id ou X-Clinic-Id se permitido e válido.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $headerOrgId = $request->header('X-Organization-Id') ?? $request->header('X-Clinic-Id');
        $requestedOrgId = ($headerOrgId !== null && $headerOrgId !== '') ? (int) $headerOrgId : null;

        $organizationId = $this->organizationAccess->resolveOrganizationIdForUser($user, $requestedOrgId);

        if ($organizationId !== null) {
            session([
                'current_clinic_id' => $organizationId,
                'current_organization_id' => $organizationId,
            ]);
            $organization = Organization::query()->find($organizationId);
        }

        return $next($request);
    }
}
