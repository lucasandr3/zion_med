<?php

namespace App\Http\Middleware;

use App\Services\TenantContextService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetClinicForApi
{
    public function __construct(
        private readonly TenantContextService $tenantContext,
    ) {}

    /**
     * Define a organização do contexto para requisições API (Sanctum).
     * Ordem: header X-Organization-Id → ability tenant:* no token → org padrão do usuário.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            $this->tenantContext->establishFromRequest($request);
        }

        return $next($request);
    }
}
