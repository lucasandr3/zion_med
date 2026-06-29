<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerifiedApi
{
    public function handle(Request $request, Closure $next, ?string $redirectToRoute = null): Response
    {
        $user = $request->user();

        if (! $user
            || ($user instanceof MustVerifyEmail && $user->hasVerifiedEmail())
            || ! ($user instanceof MustVerifyEmail)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Verifique seu e-mail para continuar.',
                'code' => 'email_unverified',
            ], 403);
        }

        return redirect()->guest($redirectToRoute ? route($redirectToRoute) : route('verification.notice'));
    }
}
