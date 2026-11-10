<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff', false);
        $response->headers->set('X-Frame-Options', 'DENY', false);
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin', false);
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()', false);
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin', false);

        // /docs/api (Scalar via Scramble): CDN jsDelivr + proxy try-it + inline script.
        if ($this->isApiDocsRequest($request)) {
            $response->headers->set(
                'Content-Security-Policy',
                "default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://unpkg.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://unpkg.com 'unsafe-hashes'; img-src 'self' data: https:; font-src 'self' data: https://cdn.jsdelivr.net https://unpkg.com; connect-src 'self' https://proxy.scalar.com; worker-src 'self' blob:; frame-ancestors 'none'; base-uri 'none'",
                false
            );
        } else {
            // API/JSON: CSP mínima anti-framing; SPAs têm CSP no nginx do front/site.
            $response->headers->set(
                'Content-Security-Policy',
                "default-src 'none'; frame-ancestors 'none'; base-uri 'none'",
                false
            );
        }

        if ($request->secure() || app()->environment('production')) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains',
                false
            );
        }

        return $response;
    }

    private function isApiDocsRequest(Request $request): bool
    {
        return $request->is('docs', 'docs/*');
    }
}
