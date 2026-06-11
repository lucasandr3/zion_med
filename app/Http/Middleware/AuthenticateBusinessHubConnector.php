<?php

namespace App\Http\Middleware;

use App\Services\BusinessHubConfigService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateBusinessHubConnector
{
    public function __construct(
        private readonly BusinessHubConfigService $businessHub,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->businessHub->isEnabled()) {
            return response()->json([
                'error' => [
                    'code' => 'CONNECTOR_DISABLED',
                    'message' => 'Integração Business Hub desativada na plataforma.',
                ],
            ], 503);
        }

        $expected = $this->businessHub->getConnectorToken();

        if (! is_string($expected) || $expected === '') {
            return response()->json([
                'error' => [
                    'code' => 'CONNECTOR_DISABLED',
                    'message' => 'API do conector não configurada no servidor.',
                ],
            ], 503);
        }

        $auth = $request->header('Authorization', '');
        if (! preg_match('/^Bearer\s+(.+)$/i', $auth, $matches)) {
            return response()->json([
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Token Bearer ausente ou inválido.',
                ],
            ], 401);
        }

        $token = trim($matches[1]);
        if (! hash_equals($expected, $token)) {
            return response()->json([
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Token Bearer inválido.',
                ],
            ], 401);
        }

        $tenantId = $request->header('X-Tenant-Id');
        if (! is_string($tenantId) || trim($tenantId) === '') {
            return response()->json([
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'Header X-Tenant-Id é obrigatório.',
                ],
            ], 403);
        }

        $request->attributes->set('business_hub_tenant_id', trim($tenantId));
        $request->attributes->set('business_hub_request_id', $request->header('X-Request-Id'));

        return $next($request);
    }
}
