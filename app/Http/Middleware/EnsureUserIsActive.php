<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && $user->getAttribute('active') === false) {
            $user->currentAccessToken()?->delete();

            return response()->json([
                'message' => 'Conta desativada.',
            ], 401);
        }

        return $next($request);
    }
}
