<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! method_exists($user, 'isPlatformAdmin') || ! $user->isPlatformAdmin()) {
            abort(403, 'Apenas administradores da plataforma podem acessar esta área.');
        }

        return $next($request);
    }
}

