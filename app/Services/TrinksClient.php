<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TrinksClient
{
    /**
     * @return array<string, mixed>
     */
    public function ping(string $apiKey, ?string $baseUrl = null): array
    {
        return $this->get($apiKey, '/v1/estabelecimentos', [], $baseUrl);
    }

    /**
     * @param  array<string, scalar|null>  $query
     * @return array<string, mixed>
     */
    public function get(string $apiKey, string $path, array $query = [], ?string $baseUrl = null): array
    {
        $response = $this->request($apiKey, $baseUrl)->get($path, $query);

        return $this->decodeSuccess($response);
    }

    public function normalizeBaseUrl(string $baseUrl): string
    {
        return rtrim(trim($baseUrl), '/');
    }

    private function request(string $apiKey, ?string $baseUrl = null): \Illuminate\Http\Client\PendingRequest
    {
        $url = $this->normalizeBaseUrl($baseUrl ?? (string) config('trinks.base_url'));
        if ($url === '') {
            throw new RuntimeException('Base URL do Trinks não configurada.');
        }

        $apiKey = trim($apiKey);
        if ($apiKey === '') {
            throw new RuntimeException('API Key do Trinks não informada.');
        }

        return Http::baseUrl($url)
            ->withHeaders([
                'X-Api-Key' => $apiKey,
                'Accept' => 'application/json',
            ])
            ->timeout((int) config('trinks.timeout_seconds', 15))
            ->connectTimeout((int) config('trinks.connect_timeout_seconds', 8));
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeSuccess(\Illuminate\Http\Client\Response $response): array
    {
        try {
            $response->throw();
        } catch (RequestException $e) {
            $json = $response->json();
            $message = is_array($json) && isset($json['message']) && is_string($json['message'])
                ? $json['message']
                : null;
            throw new RuntimeException($message ?? ('Erro Trinks HTTP '.$response->status().'.'), 0, $e);
        }

        $json = $response->json();
        if (! is_array($json)) {
            // Algumas rotas devolvem lista pura
            return ['content' => $json];
        }

        /** @var array<string, mixed> $json */
        return $json;
    }
}
