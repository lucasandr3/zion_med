<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FeegowClient
{
    /**
     * Executa um ping simples para validar token/base URL.
     *
     * @return array<string, mixed>
     */
    public function ping(string $token, ?string $baseUrl = null): array
    {
        return $this->get($token, '/company/list-local', [], $baseUrl);
    }

    /**
     * @return array<string, mixed>
     */
    public function listSpecialties(string $token, ?int $unidadeId = null, ?string $baseUrl = null): array
    {
        $params = [];
        if ($unidadeId !== null) {
            $params['unidade_id'] = $unidadeId;
        }

        return $this->get($token, '/specialties/list', $params, $baseUrl);
    }

    /**
     * @return array<string, mixed>
     */
    public function listInsurances(string $token, ?int $unidadeId = null, ?string $baseUrl = null): array
    {
        $params = [];
        if ($unidadeId !== null) {
            $params['unidade_id'] = $unidadeId;
        }

        return $this->get($token, '/insurance/list', $params, $baseUrl);
    }

    /**
     * @return array<string, mixed>
     */
    public function listAppointmentStatus(string $token, ?string $baseUrl = null): array
    {
        return $this->get($token, '/appoints/status', [], $baseUrl);
    }

    /**
     * @return array<string, mixed>
     */
    public function listAppointmentChannels(string $token, ?string $baseUrl = null): array
    {
        return $this->get($token, '/appoints/list-channel', [], $baseUrl);
    }

    /**
     * @return array<string, mixed>
     */
    public function listAppointmentMotives(string $token, ?string $baseUrl = null): array
    {
        return $this->get($token, '/appoints/motives', [], $baseUrl);
    }

    /**
     * @return array<string, mixed>
     */
    public function listUnits(string $token, ?string $baseUrl = null): array
    {
        return $this->get($token, '/company/list-unity', [], $baseUrl);
    }

    /**
     * @return array<string, mixed>
     */
    public function listLocals(string $token, ?string $baseUrl = null): array
    {
        return $this->get($token, '/company/list-local', [], $baseUrl);
    }

    /**
     * @param  array<string, scalar|null>  $filters
     * @return array<string, mixed>
     */
    public function availableSchedule(string $token, array $filters, ?string $baseUrl = null): array
    {
        return $this->get($token, '/appoints/available-schedule', $filters, $baseUrl);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createAppointment(string $token, array $payload, ?string $baseUrl = null): array
    {
        return $this->post($token, '/appoints/new-appoint', $payload, $baseUrl);
    }

    /**
     * @param  array<string, scalar|null>  $query
     * @return array<string, mixed>
     */
    public function get(string $token, string $path, array $query = [], ?string $baseUrl = null): array
    {
        $response = $this->request($token, $baseUrl)->get($path, $query);

        return $this->decodeSuccess($response);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function post(string $token, string $path, array $payload, ?string $baseUrl = null): array
    {
        $response = $this->request($token, $baseUrl)->post($path, $payload);

        return $this->decodeSuccess($response);
    }

    public function normalizeBaseUrl(string $baseUrl): string
    {
        return rtrim(trim($baseUrl), '/');
    }

    private function request(string $token, ?string $baseUrl = null): \Illuminate\Http\Client\PendingRequest
    {
        $url = $this->normalizeBaseUrl($baseUrl ?? (string) config('feegow.base_url'));
        if ($url === '') {
            throw new RuntimeException('Base URL do Feegow não configurada.');
        }

        $token = trim($token);
        if ($token === '') {
            throw new RuntimeException('Token do Feegow não informado.');
        }

        return Http::baseUrl($url)
            ->withHeaders([
                'x-access-token' => $token,
                'Accept' => 'application/json',
            ])
            ->timeout((int) config('feegow.timeout_seconds', 15))
            ->connectTimeout((int) config('feegow.connect_timeout_seconds', 8));
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
            if (is_array($json) && isset($json['message']) && is_string($json['message'])) {
                $message = $json['message'];
            }
            throw new RuntimeException($message ?? ('Erro Feegow HTTP '.$response->status().'.'), 0, $e);
        }

        $json = $response->json();
        if (! is_array($json)) {
            throw new RuntimeException('Resposta inválida da API Feegow.');
        }

        /** @var array<string, mixed> $json */
        return $json;
    }
}
