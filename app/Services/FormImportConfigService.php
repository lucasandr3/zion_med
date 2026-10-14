<?php

namespace App\Services;

use App\Models\PlatformSetting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

class FormImportConfigService
{
    public const KEY_API_KEY = 'openai_api_key';

    public const KEY_PROVIDER = 'form_import_provider';

    public const KEY_MODEL = 'form_import_openai_model';

    public const KEY_BASE_URL = 'form_import_openai_base_url';

    public const KEY_RETENTION_DAYS = 'form_import_retention_days';

    private static bool $defaultsEnsured = false;

    public function getApiKey(): ?string
    {
        $this->ensureDefaults();

        return $this->decryptSetting(self::KEY_API_KEY)
            ?? $this->normalizeEnvSecret(env('OPENAI_API_KEY'));
    }

    public function getProvider(): string
    {
        $this->ensureDefaults();

        $value = PlatformSetting::get(self::KEY_PROVIDER);
        if (is_string($value) && in_array($value, ['openai', 'heuristic'], true)) {
            return $value;
        }

        $env = strtolower((string) env('FORM_IMPORT_PROVIDER', 'openai'));

        return in_array($env, ['openai', 'heuristic'], true) ? $env : 'openai';
    }

    public function getModel(): string
    {
        $this->ensureDefaults();

        $value = PlatformSetting::get(self::KEY_MODEL);
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }

        $env = env('FORM_IMPORT_OPENAI_MODEL', 'gpt-4o-mini');

        return is_string($env) && trim($env) !== '' ? trim($env) : 'gpt-4o-mini';
    }

    public function getBaseUrl(): string
    {
        $this->ensureDefaults();

        $value = PlatformSetting::get(self::KEY_BASE_URL);
        if (is_string($value) && trim($value) !== '') {
            return rtrim(trim($value), '/');
        }

        $env = env('OPENAI_BASE_URL', 'https://api.openai.com/v1');
        $url = is_string($env) && trim($env) !== '' ? trim($env) : 'https://api.openai.com/v1';

        return rtrim($url, '/');
    }

    public function getRetentionDays(): int
    {
        $this->ensureDefaults();
        $value = PlatformSetting::get(self::KEY_RETENTION_DAYS);
        if (is_numeric($value)) {
            return max(1, min(365, (int) $value));
        }
        return max(1, min(365, (int) config('form_import.retention_days', 30)));
    }

    public function isConfigured(): bool
    {
        if ($this->getProvider() === 'heuristic') {
            return true;
        }

        $key = $this->getApiKey();

        return is_string($key) && $key !== '';
    }

    public function getApiKeyPreview(): ?string
    {
        return $this->secretPreview($this->getApiKey());
    }

    /**
     * @return array<string, mixed>
     */
    public function toSettingsPayload(): array
    {
        return [
            'provider' => $this->getProvider(),
            'model' => $this->getModel(),
            'base_url' => $this->getBaseUrl(),
            'configured' => $this->isConfigured(),
            'retention_days' => $this->getRetentionDays(),
            'api_key_preview' => $this->getApiKeyPreview(),
        ];
    }

    /**
     * @param  array{
     *     provider?: string|null,
     *     api_key?: string|null,
     *     model?: string|null,
     *     base_url?: string|null
     * }  $data
     */
    public function update(array $data): void
    {
        if (array_key_exists('provider', $data) && $data['provider'] !== null) {
            $provider = strtolower(trim((string) $data['provider']));
            if (in_array($provider, ['openai', 'heuristic'], true)) {
                PlatformSetting::set(self::KEY_PROVIDER, $provider);
            }
        }

        if (array_key_exists('api_key', $data)) {
            $plain = is_string($data['api_key']) ? trim($data['api_key']) : '';
            if ($plain !== '') {
                PlatformSetting::set(self::KEY_API_KEY, Crypt::encryptString($plain));
            }
        }

        if (array_key_exists('model', $data) && $data['model'] !== null) {
            $model = trim((string) $data['model']);
            if ($model !== '') {
                PlatformSetting::set(self::KEY_MODEL, $model);
            }
        }

        if (array_key_exists('base_url', $data) && $data['base_url'] !== null) {
            $url = trim((string) $data['base_url']);
            if ($url !== '') {
                PlatformSetting::set(self::KEY_BASE_URL, rtrim($url, '/'));
            }
        }

        if (array_key_exists('retention_days', $data) && $data['retention_days'] !== null) {
            $days = max(1, min(365, (int) $data['retention_days']));
            PlatformSetting::set(self::KEY_RETENTION_DAYS, $days);
        }
    }

    public function mergeIntoConfig(): void
    {
        if (! Schema::hasTable('platform_settings')) {
            return;
        }

        Config::set('form_import.provider', $this->getProvider());
        Config::set('form_import.openai_api_key', $this->getApiKey() ?? '');
        Config::set('form_import.openai_model', $this->getModel());
        Config::set('form_import.openai_base_url', $this->getBaseUrl());
        Config::set('form_import.retention_days', $this->getRetentionDays());
    }

    public function ensureDefaults(): void
    {
        if (self::$defaultsEnsured || ! Schema::hasTable('platform_settings')) {
            return;
        }

        self::$defaultsEnsured = true;

        $provider = env('FORM_IMPORT_PROVIDER', 'openai');
        if (is_string($provider) && in_array($provider, ['openai', 'heuristic'], true)) {
            $this->setIfMissing(self::KEY_PROVIDER, $provider);
        }

        $model = env('FORM_IMPORT_OPENAI_MODEL', 'gpt-4o-mini');
        if (is_string($model) && $model !== '') {
            $this->setIfMissing(self::KEY_MODEL, $model);
        }

        $baseUrl = env('OPENAI_BASE_URL', 'https://api.openai.com/v1');
        if (is_string($baseUrl) && $baseUrl !== '') {
            $this->setIfMissing(self::KEY_BASE_URL, rtrim($baseUrl, '/'));
        }

        $this->setIfMissing(self::KEY_RETENTION_DAYS, (int) env('FORM_IMPORT_RETENTION_DAYS', 30));
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
            return 'â€¢â€¢â€¢â€¢â€¢â€¢â€¢â€¢';
        }

        return substr($secret, 0, 4).'â€¢â€¢â€¢â€¢'.substr($secret, -4);
    }
}
