<?php

namespace App\Services;

use App\Models\PlatformSetting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

class AsaasConfigService
{
    public const KEY_BASE_URL = 'asaas_base_url';

    public const KEY_API_KEY = 'asaas_api_key';

    public const KEY_WEBHOOK_SECRET = 'asaas_webhook_secret';

    private static bool $defaultsEnsured = false;

    public function getBaseUrl(): string
    {
        $this->ensureDefaults();

        $value = PlatformSetting::get(self::KEY_BASE_URL);
        if (is_string($value) && $value !== '') {
            return rtrim($value, '/');
        }

        return rtrim((string) config('asaas.base_url', 'https://sandbox.asaas.com/api/v3'), '/');
    }

    public function getApiKey(): ?string
    {
        $this->ensureDefaults();

        return $this->decryptSetting(self::KEY_API_KEY)
            ?? $this->normalizeEnvSecret(env('ASAAS_API_KEY'));
    }

    public function getWebhookSecret(): ?string
    {
        $this->ensureDefaults();

        return $this->decryptSetting(self::KEY_WEBHOOK_SECRET)
            ?? $this->normalizeEnvSecret(env('ASAAS_WEBHOOK_SECRET'));
    }

    public function isConfigured(): bool
    {
        $key = $this->getApiKey();

        return is_string($key) && $key !== '';
    }

    public function getApiKeyPreview(): ?string
    {
        return $this->secretPreview($this->getApiKey());
    }

    public function getWebhookSecretPreview(): ?string
    {
        return $this->secretPreview($this->getWebhookSecret());
    }

    /**
     * @return array<string, mixed>
     */
    public function toSettingsPayload(): array
    {
        return [
            'base_url' => $this->getBaseUrl(),
            'api_configured' => $this->isConfigured(),
            'api_key_preview' => $this->getApiKeyPreview(),
            'webhook_secret_preview' => $this->getWebhookSecretPreview(),
        ];
    }

    /**
     * @param  array{base_url?: string, api_key?: string|null, webhook_secret?: string|null}  $data
     */
    public function update(array $data): void
    {
        if (isset($data['base_url'])) {
            PlatformSetting::set(self::KEY_BASE_URL, rtrim((string) $data['base_url'], '/'));
        }

        if (array_key_exists('api_key', $data)) {
            $plain = is_string($data['api_key']) ? trim($data['api_key']) : '';
            if ($plain !== '') {
                PlatformSetting::set(self::KEY_API_KEY, Crypt::encryptString($plain));
            }
        }

        if (array_key_exists('webhook_secret', $data)) {
            $plain = is_string($data['webhook_secret']) ? trim($data['webhook_secret']) : '';
            if ($plain !== '') {
                PlatformSetting::set(self::KEY_WEBHOOK_SECRET, Crypt::encryptString($plain));
            }
        }
    }

    public function mergeIntoConfig(): void
    {
        if (! Schema::hasTable('platform_settings')) {
            return;
        }

        $fileConfig = Config::get('asaas', []);

        Config::set('asaas', array_merge($fileConfig, [
            'base_url' => $this->getBaseUrl(),
            'api_key' => $this->getApiKey() ?? '',
            'webhook_secret' => $this->getWebhookSecret() ?? '',
        ]));
    }

    public function ensureDefaults(): void
    {
        if (self::$defaultsEnsured || ! Schema::hasTable('platform_settings')) {
            return;
        }

        self::$defaultsEnsured = true;

        $this->setIfMissing(
            self::KEY_BASE_URL,
            (string) config('asaas.base_url', 'https://sandbox.asaas.com/api/v3')
        );
    }

    private function setIfMissing(string $key, mixed $value): void
    {
        $current = PlatformSetting::get($key);
        if ($current !== null && $current !== '') {
            return;
        }

        PlatformSetting::set($key, $value);
    }

    private function decryptSetting(string $key): ?string
    {
        $encrypted = PlatformSetting::get($key);
        if (! is_string($encrypted) || $encrypted === '') {
            return null;
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (DecryptException) {
            return null;
        }
    }

    private function normalizeEnvSecret(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }

    private function secretPreview(?string $secret): ?string
    {
        if ($secret === null || $secret === '') {
            return null;
        }

        if (strlen($secret) <= 8) {
            return '••••••••';
        }

        return substr($secret, 0, 4).'••••'.substr($secret, -4);
    }
}
