<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Access-Control-Allow-Credentials só para o SPA.
 * O site de marketing continua com CORS simples, sem ler a sessão.
 */
class LimitCorsCredentials
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $origin = $request->headers->get('Origin');
        if (! is_string($origin) || ! $this->isSpaOrigin($origin)) {
            $response->headers->remove('Access-Control-Allow-Credentials');
        }

        return $response;
    }

    private function isSpaOrigin(string $origin): bool
    {
        $host = parse_url($origin, PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            return false;
        }

        $host = strtolower($host);
        $spaHosts = [
            'app.gestgo.com.br',
            'homolog.gestgo.com.br',
            'localhost',
            '127.0.0.1',
            'zion_med.test',
        ];

        return in_array($host, $spaHosts, true);
    }
}
