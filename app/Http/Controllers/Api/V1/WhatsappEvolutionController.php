<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Services\EvolutionGoClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class WhatsappEvolutionController extends Controller
{
    public function __construct(
        private readonly EvolutionGoClient $evolution
    ) {}

    /**
     * Estado da integração Evolution Go para a empresa atual.
     */
    public function show(Request $request): JsonResponse
    {
        $this->authorize('manage-clinic');
        $organization = $this->currentOrganization($request);
        if (! $organization) {
            return response()->json(['message' => 'Selecione uma empresa.'], 422);
        }

        $serverConfigured = $this->evolution->isConfigured();
        $instanceConfigured = $organization->evolution_go_instance_name
            && $organization->evolution_go_instance_token;

        $connected = null;
        $loggedIn = null;
        $remoteError = null;

        if ($serverConfigured && $instanceConfigured) {
            try {
                $token = $organization->evolution_go_instance_token;
                if (! is_string($token) || $token === '') {
                    throw new RuntimeException('Token da instância ausente.');
                }
                $raw = $this->evolution->instanceStatus($token);
                $parsed = EvolutionGoClient::parseConnectionStatus($raw);
                $connected = $parsed['connected'];
                $loggedIn = $parsed['logged_in'];
            } catch (\Throwable $e) {
                Log::warning('Evolution Go status falhou', [
                    'organization_id' => $organization->id,
                    'error' => $e->getMessage(),
                ]);
                $remoteError = $e->getMessage();
            }
        }

        return response()->json([
            'data' => [
                'server_configured' => $serverConfigured,
                'instance_configured' => (bool) $instanceConfigured,
                'instance_name' => $organization->evolution_go_instance_name,
                'has_instance_token' => (bool) $organization->evolution_go_instance_token,
                'remote_id' => $organization->evolution_go_remote_id,
                'connected' => $connected,
                'logged_in' => $loggedIn,
                'remote_error' => $remoteError,
            ],
        ]);
    }

    /**
     * Cria instância na Evolution Go e associa à empresa (token gravado criptografado).
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('manage-clinic');
        $organization = $this->currentOrganization($request);
        if (! $organization) {
            return response()->json(['message' => 'Selecione uma empresa.'], 422);
        }

        if (! $this->evolution->isConfigured()) {
            return response()->json([
                'message' => 'Evolution Go não está configurado no servidor (EVOLUTION_GO_BASE_URL / EVOLUTION_GO_API_KEY).',
            ], 503);
        }

        if ($organization->evolution_go_instance_token) {
            return response()->json([
                'message' => 'Esta empresa já possui uma instância. Remova a conexão antes de criar outra.',
            ], 422);
        }

        $name = 'zion_org_'.$organization->id;
        $generatedToken = (string) Str::uuid();

        try {
            $raw = $this->evolution->createInstance($name, $generatedToken);
            $parsed = EvolutionGoClient::parseInstanceFromCreateResponse($raw);
        } catch (\Throwable $e) {
            Log::error('Evolution Go create instance', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => $e->getMessage(),
            ], 502);
        }

        $token = $parsed['token'] ?? null;
        if ($token === null || $token === '') {
            $token = $generatedToken;
        }

        $organization->forceFill([
            'evolution_go_instance_name' => $parsed['name'] ?? $name,
            'evolution_go_remote_id' => $parsed['id'],
            'evolution_go_instance_token' => $token,
        ])->save();

        return response()->json([
            'data' => [
                'instance_name' => $organization->evolution_go_instance_name,
                'remote_id' => $organization->evolution_go_remote_id,
                /** Mesmo valor enviado à API; exibir só uma vez na UI. */
                'instance_token' => $token,
            ],
        ], 201);
    }

    /**
     * Inicia fluxo de conexão (QR ou código de pareamento no WhatsApp).
     */
    public function connect(Request $request): JsonResponse
    {
        $this->authorize('manage-clinic');
        $organization = $this->requireInstance($request);
        if ($organization instanceof JsonResponse) {
            return $organization;
        }

        $validated = $request->validate([
            'phone' => ['nullable', 'string', 'max:32'],
            'webhook_url' => ['nullable', 'string', 'max:2048', 'url'],
            'subscribe' => ['nullable', 'array'],
            'subscribe.*' => ['string', 'max:64'],
            'immediate' => ['nullable', 'boolean'],
        ]);

        $phone = isset($validated['phone']) ? preg_replace('/\D+/', '', (string) $validated['phone']) : null;
        if ($phone === '') {
            $phone = null;
        }

        $subscribe = $validated['subscribe'] ?? null;
        $webhook = $validated['webhook_url'] ?? null;
        $immediate = (bool) ($validated['immediate'] ?? false);

        try {
            $token = (string) $organization->evolution_go_instance_token;
            $this->evolution->connectInstance($token, $phone, $webhook, $subscribe, $immediate);
        } catch (\Throwable $e) {
            Log::error('Evolution Go connect', ['error' => $e->getMessage()]);

            return response()->json(['message' => $e->getMessage()], 502);
        }

        return response()->json([
            'data' => ['message' => 'Conexão iniciada. Use QR Code ou código de pareamento.'],
        ]);
    }

    public function qr(Request $request): JsonResponse
    {
        $this->authorize('manage-clinic');
        $organization = $this->requireInstance($request);
        if ($organization instanceof JsonResponse) {
            return $organization;
        }

        try {
            $token = (string) $organization->evolution_go_instance_token;
            $raw = $this->evolution->instanceQr($token);
            $parsed = EvolutionGoClient::parseQrResponse($raw);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        return response()->json([
            'data' => [
                'qrcode' => $parsed['qrcode'],
                'link_code' => $parsed['code'],
            ],
        ]);
    }

    public function pair(Request $request): JsonResponse
    {
        $this->authorize('manage-clinic');
        $organization = $this->requireInstance($request);
        if ($organization instanceof JsonResponse) {
            return $organization;
        }

        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:32'],
            'subscribe' => ['nullable', 'array'],
            'subscribe.*' => ['string', 'max:64'],
        ]);

        $phone = preg_replace('/\D+/', '', $validated['phone']);
        if (strlen($phone) < 10) {
            return response()->json(['message' => 'Informe o número com DDI e DDD (ex.: 5511999999999).'], 422);
        }

        try {
            $token = (string) $organization->evolution_go_instance_token;
            $raw = $this->evolution->requestPairing($token, $phone, $validated['subscribe'] ?? null);
            $code = EvolutionGoClient::parsePairingCode($raw);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        return response()->json([
            'data' => [
                'pairing_code' => $code,
            ],
        ]);
    }

    public function disconnect(Request $request): JsonResponse
    {
        $this->authorize('manage-clinic');
        $organization = $this->requireInstance($request);
        if ($organization instanceof JsonResponse) {
            return $organization;
        }

        try {
            $token = (string) $organization->evolution_go_instance_token;
            $this->evolution->disconnect($token);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        return response()->json(['data' => ['message' => 'Instância desconectada na Evolution Go.']]);
    }

    /**
     * Remove a instância na Evolution Go e limpa os dados locais.
     */
    public function destroy(Request $request): JsonResponse
    {
        $this->authorize('manage-clinic');
        $organization = $this->requireInstance($request);
        if ($organization instanceof JsonResponse) {
            return $organization;
        }

        if (! $this->evolution->isConfigured()) {
            return response()->json(['message' => 'Evolution Go não configurado.'], 503);
        }

        $remoteId = $organization->evolution_go_remote_id;
        if (! $remoteId) {
            try {
                foreach ($this->evolution->listAllInstances() as $row) {
                    $n = $row['name'] ?? null;
                    if ((string) $n === (string) $organization->evolution_go_instance_name) {
                        $remoteId = isset($row['id']) ? (string) $row['id'] : null;
                        break;
                    }
                }
            } catch (\Throwable) {
                // segue para limpar só local
            }
        }

        if ($remoteId) {
            try {
                $this->evolution->deleteRemoteInstance($remoteId);
            } catch (\Throwable $e) {
                Log::warning('Evolution Go delete instance', ['error' => $e->getMessage()]);
            }
        }

        $organization->forceFill([
            'evolution_go_instance_name' => null,
            'evolution_go_remote_id' => null,
            'evolution_go_instance_token' => null,
        ])->save();

        return response()->json(['data' => ['message' => 'Integração WhatsApp removida.']]);
    }

    /**
     * Envia mensagem de teste (número em formato internacional, só dígitos ou com +).
     */
    public function testMessage(Request $request): JsonResponse
    {
        $this->authorize('manage-clinic');
        $organization = $this->requireInstance($request);
        if ($organization instanceof JsonResponse) {
            return $organization;
        }

        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:32'],
            'text' => ['nullable', 'string', 'max:4096'],
        ]);

        $phone = preg_replace('/\D+/', '', $validated['phone']);
        if (strlen($phone) < 10) {
            return response()->json(['message' => 'Número inválido. Use DDI + DDD + número.'], 422);
        }

        $text = isset($validated['text']) && trim($validated['text']) !== ''
            ? trim($validated['text'])
            : 'Mensagem de teste — Zion Med / Evolution Go.';

        try {
            $token = (string) $organization->evolution_go_instance_token;
            $this->evolution->sendText($token, $phone, $text);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        return response()->json([
            'data' => ['message' => 'Mensagem enviada.'],
        ]);
    }

    private function currentOrganization(Request $request): ?Organization
    {
        $organizationId = session('current_organization_id') ?? session('current_clinic_id');
        if (! $organizationId) {
            return null;
        }

        return Organization::query()->find($organizationId);
    }

    private function requireInstance(Request $request): Organization|JsonResponse
    {
        $organization = $this->currentOrganization($request);
        if (! $organization) {
            return response()->json(['message' => 'Selecione uma empresa.'], 422);
        }
        if (! $organization->evolution_go_instance_token || ! $organization->evolution_go_instance_name) {
            return response()->json(['message' => 'Crie uma instância WhatsApp antes desta ação.'], 422);
        }

        return $organization;
    }
}
