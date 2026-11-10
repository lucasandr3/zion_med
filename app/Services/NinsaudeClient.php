<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class NinsaudeClient
{
    /**
     * Valida credenciais: refresh_token OU login (conta/usuário/senha), depois pinga pacientes.
     *
     * @return array<string, mixed>
     */
    public function ping(
        ?string $refreshToken = null,
        ?string $account = null,
        ?string $username = null,
        ?string $password = null,
        ?string $baseUrl = null,
    ): array {
        $accessToken = $this->resolveAccessToken($refreshToken, $account, $username, $password, $baseUrl);

        return $this->getWithBearer($accessToken, '/pacientes', ['limit' => 1], $baseUrl);
    }

    public function normalizeBaseUrl(string $baseUrl): string
    {
        return rtrim(trim($baseUrl), '/');
    }

    private function resolveAccessToken(
        ?string $refreshToken,
        ?string $account,
        ?string $username,
        ?string $password,
        ?string $baseUrl,
    ): string {
        $refreshToken = trim((string) $refreshToken);
        if ($refreshToken !== '') {
            return $this->accessTokenFromRefresh($refreshToken, $baseUrl);
        }

        $account = trim((string) $account);
        $username = trim((string) $username);
        $password = (string) $password;
        if ($account === '' || $username === '' || trim($password) === '') {
            throw new RuntimeException('Informe o refresh token OU conta, usuário e senha da Ninsaúde.');
        }

        return $this->accessTokenFromLogin($account, $username, $password, $baseUrl);
    }

    private function accessTokenFromRefresh(string $refreshToken, ?string $baseUrl): string
    {
        $url = $this->normalizeBaseUrl($baseUrl ?? (string) config('ninsaude.base_url'));
        $response = Http::baseUrl($url)
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('ninsaude.timeout_seconds', 20))
            ->connectTimeout((int) config('ninsaude.connect_timeout_seconds', 8))
            ->post('/oauth/token', [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
            ]);

        try {
            $response->throw();
        } catch (RequestException $e) {
            // Fallback: algumas contas usam /auth/refresh
            $fallback = Http::baseUrl($url)
                ->acceptJson()
                ->asJson()
                ->timeout((int) config('ninsaude.timeout_seconds', 20))
                ->post('/auth/refresh', ['refresh_token' => $refreshToken]);

            try {
                $fallback->throw();
            } catch (RequestException $e2) {
                throw new RuntimeException($this->extractError($fallback) ?? 'Falha ao renovar token Ninsaúde.', 0, $e2);
            }

            $json = $fallback->json();
            $token = is_array($json) ? (string) ($json['access_token'] ?? '') : '';
            if ($token === '') {
                throw new RuntimeException('Refresh Ninsaúde não retornou access_token.');
            }

            return $token;
        }

        $json = $response->json();
        $token = is_array($json) ? (string) ($json['access_token'] ?? '') : '';
        if ($token === '') {
            throw new RuntimeException('Refresh Ninsaúde não retornou access_token.');
        }

        return $token;
    }

    private function accessTokenFromLogin(string $account, string $username, string $password, ?string $baseUrl): string
    {
        $url = $this->normalizeBaseUrl($baseUrl ?? (string) config('ninsaude.base_url'));
        $response = Http::baseUrl($url)
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('ninsaude.timeout_seconds', 20))
            ->connectTimeout((int) config('ninsaude.connect_timeout_seconds', 8))
            ->post('/auth/login', [
                'conta' => $account,
                'username' => $username,
                'password' => $password,
            ]);

        try {
            $response->throw();
        } catch (RequestException $e) {
            throw new RuntimeException($this->extractError($response) ?? ('Erro Ninsaúde login HTTP '.$response->status().'.'), 0, $e);
        }

        $json = $response->json();
        $token = is_array($json) ? (string) ($json['access_token'] ?? '') : '';
        if ($token === '') {
            throw new RuntimeException('Login Ninsaúde não retornou access_token.');
        }

        return $token;
    }

    /**
     * @param  array<string, scalar|null>  $query
     * @return array<string, mixed>
     */
    private function getWithBearer(string $accessToken, string $path, array $query = [], ?string $baseUrl = null): array
    {
        $url = $this->normalizeBaseUrl($baseUrl ?? (string) config('ninsaude.base_url'));
        $response = Http::baseUrl($url)
            ->withToken($accessToken)
            ->acceptJson()
            ->timeout((int) config('ninsaude.timeout_seconds', 20))
            ->connectTimeout((int) config('ninsaude.connect_timeout_seconds', 8))
            ->get($path, $query);

        try {
            $response->throw();
        } catch (RequestException $e) {
            throw new RuntimeException($this->extractError($response) ?? ('Erro Ninsaúde HTTP '.$response->status().'.'), 0, $e);
        }

        $json = $response->json();
        if (! is_array($json)) {
            return ['content' => $json];
        }

        /** @var array<string, mixed> $json */
        return $json;
    }

    private function extractError(\Illuminate\Http\Client\Response $response): ?string
    {
        $json = $response->json();
        if (! is_array($json)) {
            return null;
        }
        if (isset($json['message']) && is_string($json['message'])) {
            return $json['message'];
        }
        if (isset($json['error']) && is_string($json['error'])) {
            return $json['error'];
        }

        return null;
    }
}
