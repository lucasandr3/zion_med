<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Cliente HTTP para Evolution Go (header apikey: global ou por instância).
 */
class EvolutionGoClient
{
    public function isConfigured(): bool
    {
        $base = config('evolution_go.base_url');
        $key = config('evolution_go.api_key');

        return $base !== '' && $key !== '';
    }

    /**
     * @return array<string, mixed>
     */
    public function createInstance(string $name, ?string $token = null): array
    {
        $payload = ['name' => $name];
        if ($token !== null && $token !== '') {
            $payload['token'] = $token;
        }

        return $this->decodeSuccess(
            $this->global()->post('/instance/create', $payload)
        );
    }

    /**
     * @param  list<string>|null  $subscribe
     * @return array<string, mixed>
     */
    public function connectInstance(string $instanceApiKey, ?string $phone = null, ?string $webhookUrl = null, ?array $subscribe = null, bool $immediate = false): array
    {
        $body = array_filter([
            'phone' => $phone !== null && $phone !== '' ? $phone : null,
            'webhookUrl' => $webhookUrl !== null && $webhookUrl !== '' ? $webhookUrl : null,
            'subscribe' => $subscribe,
            'immediate' => $immediate,
        ], fn ($v) => $v !== null);

        return $this->decodeSuccess(
            $this->forInstance($instanceApiKey)->post('/instance/connect', $body)
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function instanceStatus(string $instanceApiKey): array
    {
        return $this->decodeSuccess(
            $this->forInstance($instanceApiKey)->get('/instance/status')
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function instanceQr(string $instanceApiKey): array
    {
        return $this->decodeSuccess(
            $this->forInstance($instanceApiKey)->get('/instance/qr')
        );
    }

    /**
     * @param  list<string>|null  $subscribe
     * @return array<string, mixed>
     */
    public function requestPairing(string $instanceApiKey, string $phone, ?array $subscribe = null): array
    {
        $body = array_filter([
            'phone' => $phone,
            'subscribe' => $subscribe,
        ], fn ($v) => $v !== null);

        return $this->decodeSuccess(
            $this->forInstance($instanceApiKey)->post('/instance/pair', $body)
        );
    }

    public function disconnect(string $instanceApiKey): void
    {
        $this->decodeSuccess(
            $this->forInstance($instanceApiKey)
                ->withBody('{}', 'application/json')
                ->post('/instance/disconnect')
        );
    }

    public function deleteRemoteInstance(string $remoteInstanceId): void
    {
        $this->decodeSuccess(
            $this->global()->delete('/instance/delete/'.$remoteInstanceId)
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function sendText(string $instanceApiKey, string $number, string $text): array
    {
        return $this->decodeSuccess(
            $this->forInstance($instanceApiKey)->post('/send/text', [
                'number' => $number,
                'text' => $text,
            ])
        );
    }

    /**
     * Lista instâncias (chave global); útil para resolver remote id pelo nome.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listAllInstances(): array
    {
        $json = $this->decodeSuccess(
            $this->global()->get('/instance/all')
        );
        if (isset($json['instances']) && is_array($json['instances'])) {
            return array_values(array_filter($json['instances'], 'is_array'));
        }
        $data = $json['data'] ?? null;
        if (is_array($data)) {
            if (isset($data['instances']) && is_array($data['instances'])) {
                return array_values(array_filter($data['instances'], 'is_array'));
            }
            if (array_is_list($data)) {
                return array_values(array_filter($data, 'is_array'));
            }
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $createResponse
     * @return array{name: ?string, token: ?string, id: ?string}
     */
    public static function parseInstanceFromCreateResponse(array $createResponse): array
    {
        $data = self::unwrapData($createResponse);
        $row = $data['instance'] ?? $data;

        return [
            'name' => isset($row['name']) ? (string) $row['name'] : null,
            'token' => isset($row['token']) ? (string) $row['token'] : null,
            'id' => isset($row['id']) ? (string) $row['id'] : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $statusResponse
     * @return array{connected: ?bool, logged_in: ?bool, name: ?string}
     */
    public static function parseConnectionStatus(array $statusResponse): array
    {
        $d = self::unwrapData($statusResponse);

        return [
            'connected' => self::boolish($d['Connected'] ?? $d['connected'] ?? null),
            'logged_in' => self::boolish($d['LoggedIn'] ?? $d['logged_in'] ?? $d['loggedIn'] ?? null),
            'name' => isset($d['Name']) ? (string) $d['Name'] : (isset($d['name']) ? (string) $d['name'] : null),
        ];
    }

    /**
     * @param  array<string, mixed>  $qrResponse
     * @return array{qrcode: ?string, code: ?string}
     */
    public static function parseQrResponse(array $qrResponse): array
    {
        $d = self::unwrapData($qrResponse);

        $qr = $d['Qrcode'] ?? $d['qrcode'] ?? $d['QRCode'] ?? null;

        return [
            'qrcode' => $qr !== null ? (string) $qr : null,
            'code' => isset($d['Code']) ? (string) $d['Code'] : (isset($d['code']) ? (string) $d['code'] : null),
        ];
    }

    /**
     * @param  array<string, mixed>  $pairResponse
     */
    public static function parsePairingCode(array $pairResponse): ?string
    {
        $d = self::unwrapData($pairResponse);
        $v = $d['PairingCode'] ?? $d['pairingCode'] ?? $d['pairing_code'] ?? null;

        return $v !== null ? (string) $v : null;
    }

    private function global(): \Illuminate\Http\Client\PendingRequest
    {
        return $this->baseRequest(config('evolution_go.api_key'));
    }

    private function forInstance(string $instanceApiKey): \Illuminate\Http\Client\PendingRequest
    {
        return $this->baseRequest($instanceApiKey);
    }

    private function baseRequest(string $apiKey): \Illuminate\Http\Client\PendingRequest
    {
        $base = config('evolution_go.base_url');
 
        if ($base === '') {
            throw new RuntimeException('Evolution Go não configurado (EVOLUTION_GO_BASE_URL).');
        }

        return Http::baseUrl($base)
            ->withHeaders([
                'apikey' => $apiKey,
                'Accept' => 'application/json',
            ])
            ->timeout(90)
            ->connectTimeout(15);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeSuccess(\Illuminate\Http\Client\Response $response): array
    {
        try {
            $response->throw();
        } catch (RequestException $e) {
            $body = $response->json();
            $msg = is_array($body) && isset($body['message']) && is_string($body['message'])
                ? $body['message']
                : null;
            if ($msg === null && is_array($body) && isset($body['error']['message']) && is_string($body['error']['message'])) {
                $msg = $body['error']['message'];
            }
            throw new RuntimeException($msg ?? 'Erro na API Evolution Go (HTTP '.$response->status().').', 0, $e);
        }

        $json = $response->json();
        if (! is_array($json)) {
            throw new RuntimeException('Resposta inválida da Evolution Go.');
        }

        /** @var array<string, mixed> $json */
        return $json;
    }

    /**
     * @param  array<string, mixed>  $json
     * @return array<string, mixed>
     */
    private static function unwrapData(array $json): array
    {
        $data = $json['data'] ?? $json;
        if (! is_array($data)) {
            return [];
        }

        /** @var array<string, mixed> $data */
        return $data;
    }

    private static function boolish(mixed $v): ?bool
    {
        if ($v === null) {
            return null;
        }
        if (is_bool($v)) {
            return $v;
        }
        if (is_int($v) || is_float($v)) {
            return (bool) $v;
        }
        if (is_string($v)) {
            $l = strtolower($v);

            return match ($l) {
                'true', '1', 'yes', 'open', 'connected' => true,
                'false', '0', 'no', 'close', 'disconnected' => false,
                default => null,
            };
        }

        return null;
    }
}
