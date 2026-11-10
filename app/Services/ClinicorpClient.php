<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ClinicorpClient
{
    /**
     * @return array<string, mixed>
     */
    public function ping(string $apiUser, string $apiToken, ?string $subscriberId = null, ?string $baseUrl = null): array
    {
        $query = [];
        if ($subscriberId !== null && trim($subscriberId) !== '') {
            $query['subscriber_id'] = trim($subscriberId);
        }

        return $this->get($apiUser, $apiToken, '/professional/list_all_professionals', $query, $baseUrl);
    }

    /**
     * @param  array<string, scalar|null>  $query
     * @return array<string, mixed>
     */
    public function get(
        string $apiUser,
        string $apiToken,
        string $path,
        array $query = [],
        ?string $baseUrl = null,
    ): array {
        $response = $this->request($apiUser, $apiToken, $baseUrl)->get($path, $query);

        return $this->decodeSuccess($response);
    }

    public function normalizeBaseUrl(string $baseUrl): string
    {
        return rtrim(trim($baseUrl), '/');
    }

    private function request(string $apiUser, string $apiToken, ?string $baseUrl = null): \Illuminate\Http\Client\PendingRequest
    {
        $url = $this->normalizeBaseUrl($baseUrl ?? (string) config('clinicorp.base_url'));
        if ($url === '') {
            throw new RuntimeException('Base URL do Clinicorp não configurada.');
        }

        $apiUser = trim($apiUser);
        $apiToken = trim($apiToken);
        if ($apiUser === '' || $apiToken === '') {
            throw new RuntimeException('Usuário API e Token API do Clinicorp são obrigatórios.');
        }

        return Http::baseUrl($url)
            ->withBasicAuth($apiUser, $apiToken)
            ->acceptJson()
            ->timeout((int) config('clinicorp.timeout_seconds', 15))
            ->connectTimeout((int) config('clinicorp.connect_timeout_seconds', 8));
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
            $message = null;
            if (is_array($json)) {
                if (isset($json['message']) && is_string($json['message'])) {
                    $message = $json['message'];
                } elseif (isset($json['error']) && is_string($json['error'])) {
                    $message = $json['error'];
                }
            }
            throw new RuntimeException($message ?? ('Erro Clinicorp HTTP '.$response->status().'.'), 0, $e);
        }

        $json = $response->json();
        if (! is_array($json)) {
            return ['content' => $json];
        }

        /** @var array<string, mixed> $json */
        return $json;
    }
}
