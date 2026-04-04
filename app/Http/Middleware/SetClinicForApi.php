<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetClinicForApi
{
    /**
     * Define a organização do contexto para requisições API (Sanctum).
     * Usa organization_id do usuário; header X-Organization-Id ou X-Clinic-Id se pode trocar de empresa.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $organizationId = $user->clinic_id;
        $headerOrgId = $request->header('X-Organization-Id') ?? $request->header('X-Clinic-Id');
        if ($user->canSwitchClinic() && $headerOrgId !== null && $headerOrgId !== '') {
            $organizationId = (int) $headerOrgId;
        }

        if ($organizationId !== null) {
            session([
                'current_clinic_id' => $organizationId,
                'current_organization_id' => $organizationId,
            ]);
            $organization = Organization::query()->find($organizationId);
            $organization?->syncExpiredTrialStateIfNeeded();
        }

        return $next($request);
    }
}
