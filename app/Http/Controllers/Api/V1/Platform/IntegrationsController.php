<?php

namespace App\Http\Controllers\Api\V1\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateBusinessHubIntegrationRequest;
use App\Services\BusinessHubConfigService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class IntegrationsController extends Controller
{
    public function __construct(
        private readonly BusinessHubConfigService $businessHub,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                'integrations' => [
                    $this->businessHub->toIntegrationListItem(),
                ],
            ],
        ]);
    }

    public function showBusinessHub(): JsonResponse
    {
        return response()->json([
            'data' => $this->businessHub->toDetailPayload(),
        ]);
    }

    public function updateBusinessHub(UpdateBusinessHubIntegrationRequest $request): JsonResponse
    {
        $this->businessHub->update($request->validated());

        return response()->json([
            'message' => 'Integração Business Hub atualizada.',
            'data' => $this->businessHub->toDetailPayload(),
        ]);
    }

    public function regenerateBusinessHubToken(): JsonResponse
    {
        $token = $this->businessHub->regenerateToken();

        return response()->json([
            'message' => 'Novo token gerado. Copie e configure no Business Hub.',
            'data' => [
                ...$this->businessHub->toDetailPayload(),
                'token' => $token,
            ],
        ]);
    }

    public function testBusinessHub(): JsonResponse
    {
        if (! $this->businessHub->isConfigured()) {
            return response()->json([
                'message' => 'Configure um token antes de testar a conexão.',
                'data' => ['ok' => false, 'status' => 'not_configured'],
            ], 422);
        }

        if (! $this->businessHub->isEnabled()) {
            return response()->json([
                'message' => 'A integração Business Hub está desativada.',
                'data' => ['ok' => false, 'status' => 'disabled'],
            ], 422);
        }

        $token = $this->businessHub->getConnectorToken();
        $url = $this->businessHub->getBaseUrl().'/health';

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$token,
                    'X-Tenant-Id' => 'platform-test',
                    'Accept' => 'application/json',
                ])
                ->get($url);
        } catch (ConnectionException $exception) {
            return response()->json([
                'message' => 'Não foi possível conectar ao conector: '.$exception->getMessage(),
                'data' => ['ok' => false, 'status' => 'connection_error'],
            ], 502);
        }

        if (! $response->successful()) {
            return response()->json([
                'message' => 'Health check falhou (HTTP '.$response->status().').',
                'data' => [
                    'ok' => false,
                    'status' => 'http_error',
                    'http_status' => $response->status(),
                    'body' => $response->json(),
                ],
            ], 502);
        }

        return response()->json([
            'message' => 'Conexão com o conector verificada com sucesso.',
            'data' => [
                'ok' => true,
                'status' => 'ok',
                'health' => $response->json(),
            ],
        ]);
    }
}
