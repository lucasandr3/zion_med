<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantBillingActive
{
    /**
     * Bloqueia a API tenant quando o trial acabou sem pagamento confirmado (SPA não usa o middleware web).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldBypass($request)) {
            return $next($request);
        }

        $user = $request->user();
        if (! $user || ! method_exists($user, 'isTenantUser') || ! $user->isTenantUser()) {
            return $next($request);
        }

        $orgId = session('current_organization_id') ?? session('current_clinic_id') ?? $user->clinic_id;
        if (! $orgId) {
            return $next($request);
        }

        $organization = Organization::query()->find($orgId);
        if (! $organization) {
            return $next($request);
        }

        $organization->syncExpiredTrialStateIfNeeded();
        $organization->refresh();

        if ($organization->canAccessTenantAppFeatures()) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Seu período de avaliação terminou ou a assinatura está inativa. Acesse Cobrança / Assinatura para regularizar o pagamento.',
            'code' => 'billing_blocked',
        ], 403);
    }

    private function shouldBypass(Request $request): bool
    {
        $path = $request->path();
        $prefixes = [
            'api/v1/billing',
            'api/v1/clinica/escolher',
            'api/v1/me',
            'api/v1/clinica/configuracoes',
            'api/v1/auth/logout',
        ];
        foreach ($prefixes as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return true;
            }
        }

        return false;
    }
}
