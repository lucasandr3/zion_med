<?php

namespace App\Http\Controllers\Api\V1\Connector;

use App\Http\Controllers\Controller;
use App\Services\BusinessHubConfigService;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function __construct(
        private readonly BusinessHubConfigService $businessHub,
    ) {}

    public function __invoke(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'system' => $this->businessHub->getSystemName(),
            'version' => $this->businessHub->getVersion(),
            'timestamp' => now()->toIso8601String(),
            'endpoints' => [
                'health' => '/health',
                'empresas' => '/empresas',
                'clientes' => '/clientes',
                'contatos' => '/contatos',
                'leads' => '/leads',
                'assinaturas' => '/assinaturas',
                'faturas' => '/faturas',
            ],
        ]);
    }
}
