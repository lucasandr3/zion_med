<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! method_exists($user, 'isTenantUser') || ! $user->isTenantUser()) {
            abort(403, 'Você não tem permissão para acessar a área de clínicas.');
        }

        return $next($request);
    }
}

