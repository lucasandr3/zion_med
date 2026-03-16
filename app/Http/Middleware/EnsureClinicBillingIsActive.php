<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureClinicBillingIsActive
{
    private const ALLOWED_PREFIXES = [
        'billing',
        'logout',
        'webhooks',
        'webhooks/asaas',
        'status',
        'f/',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return $next($request);
        }

        if ($this->isAllowed($request)) {
            return $next($request);
        }

        $organization = $this->currentOrganization($request);
        if (! $organization) {
            return $next($request);
        }

        $this->syncExpiredTrial($organization);

        if ($this->canAccess($organization, $request)) {
            if ($organization->subscription_status === 'past_due' && $organization->grace_ends_at && now()->lte($organization->grace_ends_at)) {
                $request->session()->flash('billing_warning', 'Pagamento pendente. Regularize até ' . $organization->grace_ends_at->format('d/m/Y') . ' para evitar a suspensão do acesso.');
            }
            return $next($request);
        }

        return redirect()
            ->route('billing.index')
            ->with('error', 'Acesso suspenso. Regularize a assinatura para continuar.');
    }

    private function isAllowed(Request $request): bool
    {
        $path = trim($request->path(), '/');
        foreach (self::ALLOWED_PREFIXES as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return true;
            }
            if (str_ends_with($prefix, '/') && str_starts_with($path, $prefix)) {
                return true;
            }
        }
        if ($request->routeIs('logout')) {
            return true;
        }
        if ($request->routeIs('billing.*')) {
            return true;
        }
        if ($request->routeIs('formulario-publico.*')) {
            return true;
        }
        if ($request->routeIs('clinica.configuracoes.*')) {
            return true;
        }
        return false;
    }

    private function currentOrganization(Request $request): ?Organization
    {
        $orgId = session('current_clinic_id') ?? $request->user()?->organization_id ?? $request->user()?->clinic_id;
        if (! $orgId) {
            return null;
        }
        return Organization::find($orgId);
    }

    private function syncExpiredTrial(Organization $organization): void
    {
        if ($organization->subscription_status !== 'trial' || ! $organization->trial_ends_at || now()->lte($organization->trial_ends_at)) {
            return;
        }
        $hasActiveSubscription = $organization->subscriptions()->where('status', 'active')->exists();
        if (! $hasActiveSubscription) {
            $organization->update([
                'subscription_status' => 'inactive',
                'billing_status'     => 'blocked',
            ]);
        }
    }

    private function canAccess(Organization $organization, Request $request): bool
    {
        if ($organization->subscription_status === 'active') {
            return true;
        }
        if ($organization->subscription_status === 'trial' && $organization->trial_ends_at && now()->lte($organization->trial_ends_at)) {
            return true;
        }
        if ($organization->subscription_status === 'past_due') {
            if ($organization->grace_ends_at && now()->lte($organization->grace_ends_at)) {
                return true;
            }
            if ($organization->grace_ends_at && now()->gt($organization->grace_ends_at)) {
                $organization->update(['billing_status' => 'blocked']);
            }
        }
        return false;
    }
}
