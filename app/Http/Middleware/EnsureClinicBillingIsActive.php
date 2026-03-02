<?php

namespace App\Http\Middleware;

use App\Models\Clinic;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureClinicBillingIsActive
{
    private const ALLOWED_PREFIXES = [
        'billing',
        'logout',
        'webhooks/asaas',
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

        $clinic = $this->currentClinic($request);
        if (! $clinic) {
            return $next($request);
        }

        $this->syncExpiredTrial($clinic);

        if ($this->canAccess($clinic, $request)) {
            if ($clinic->subscription_status === 'past_due' && $clinic->grace_ends_at && now()->lte($clinic->grace_ends_at)) {
                $request->session()->flash('billing_warning', 'Pagamento pendente. Regularize até ' . $clinic->grace_ends_at->format('d/m/Y') . ' para evitar a suspensão do acesso.');
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

    private function currentClinic(Request $request): ?Clinic
    {
        $clinicId = session('current_clinic_id') ?? $request->user()->clinic_id;
        if (! $clinicId) {
            return null;
        }
        return Clinic::find($clinicId);
    }

    private function syncExpiredTrial(Clinic $clinic): void
    {
        if ($clinic->subscription_status !== 'trial' || ! $clinic->trial_ends_at || now()->lte($clinic->trial_ends_at)) {
            return;
        }
        $hasActiveSubscription = $clinic->subscriptions()->where('status', 'active')->exists();
        if (! $hasActiveSubscription) {
            $clinic->update([
                'subscription_status' => 'inactive',
                'billing_status'     => 'blocked',
            ]);
        }
    }

    private function canAccess(Clinic $clinic, Request $request): bool
    {
        if ($clinic->subscription_status === 'active') {
            return true;
        }
        if ($clinic->subscription_status === 'trial' && $clinic->trial_ends_at && now()->lte($clinic->trial_ends_at)) {
            return true;
        }
        if ($clinic->subscription_status === 'past_due') {
            if ($clinic->grace_ends_at && now()->lte($clinic->grace_ends_at)) {
                return true;
            }
            if ($clinic->grace_ends_at && now()->gt($clinic->grace_ends_at)) {
                $clinic->update(['billing_status' => 'blocked']);
            }
        }
        return false;
    }
}
