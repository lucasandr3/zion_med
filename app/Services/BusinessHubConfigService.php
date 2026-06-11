<?php

namespace App\Services;

use App\Models\PlatformSetting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class BusinessHubConfigService
{
    public const INTEGRATION_KEY = 'business_hub';

    public const CONNECTOR_TYPES = ['CRM', 'ERP', 'BILLING', 'FINANCEIRO', 'CUSTOM'];

    public const KEY_ENABLED = 'business_hub_enabled';

    public const KEY_TOKEN = 'business_hub_connector_token';

    public const KEY_TYPE = 'business_hub_connector_type';

    public const KEY_SYSTEM_NAME = 'business_hub_system_name';

    public const KEY_VERSION = 'business_hub_connector_version';

    public const DEFAULT_TYPE = 'BILLING';

    public const DEFAULT_VERSION = '1.0.0';

    private static bool $defaultsEnsured = false;

    public function isEnabled(): bool
    {
        $this->ensureDefaults();

        $enabled = PlatformSetting::get(self::KEY_ENABLED);
        if ($enabled !== null && $enabled !== '') {
            return filter_var($enabled, FILTER_VALIDATE_BOOLEAN);
        }

        return $this->getConnectorToken() !== null;
    }

    public function getConnectorToken(): ?string
    {
        $this->ensureDefaults();

        $encrypted = PlatformSetting::get(self::KEY_TOKEN);
        if (! is_string($encrypted) || $encrypted === '') {
            return null;
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (DecryptException) {
            return null;
        }
    }

    public function getConnectorType(): string
    {
        $this->ensureDefaults();

        $value = PlatformSetting::get(self::KEY_TYPE);

        return is_string($value) && $value !== '' ? $value : self::DEFAULT_TYPE;
    }

    public function getSystemName(): string
    {
        $this->ensureDefaults();

        $value = PlatformSetting::get(self::KEY_SYSTEM_NAME);
        if (is_string($value) && $value !== '') {
            return $value;
        }

        $productName = PlatformSetting::get('product_name');

        return is_string($productName) && $productName !== ''
            ? $productName
            : (string) config('app.name', 'Gestgo');
    }

    public function getVersion(): string
    {
        $this->ensureDefaults();

        $value = PlatformSetting::get(self::KEY_VERSION);

        return is_string($value) && $value !== '' ? $value : self::DEFAULT_VERSION;
    }

    public function getBaseUrl(): string
    {
        return rtrim(url('/api/v1/conector'), '/');
    }

    public function isConfigured(): bool
    {
        return $this->getConnectorToken() !== null;
    }

    public function getTokenPreview(): ?string
    {
        $token = $this->getConnectorToken();
        if ($token === null || $token === '') {
            return null;
        }

        if (strlen($token) <= 8) {
            return '••••••••';
        }

        return substr($token, 0, 4).'••••'.substr($token, -4);
    }

    /**
     * @return array<string, mixed>
     */
    public function toIntegrationListItem(): array
    {
        $configured = $this->isConfigured();
        $enabled = $this->isEnabled();

        return [
            'id' => self::INTEGRATION_KEY,
            'key' => self::INTEGRATION_KEY,
            'name' => 'Business Hub (App Gestor)',
            'description' => 'Permite que o aplicativo Business Hub sincronize empresas, clientes, leads e faturamento desta plataforma.',
            'enabled' => $enabled,
            'configured' => $configured,
            'status' => ! $configured ? 'not_configured' : ($enabled ? 'active' : 'inactive'),
            'connector_type' => $this->getConnectorType(),
            'base_url' => $this->getBaseUrl(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDetailPayload(): array
    {
        return [
            ...$this->toIntegrationListItem(),
            'system_name' => $this->getSystemName(),
            'version' => $this->getVersion(),
            'token_configured' => $this->isConfigured(),
            'token_preview' => $this->getTokenPreview(),
            'connector_types' => self::CONNECTOR_TYPES,
            'endpoints' => [
                'health' => '/health',
                'empresas' => '/empresas',
                'clientes' => '/clientes',
                'contatos' => '/contatos',
                'leads' => '/leads',
                'assinaturas' => '/assinaturas',
                'faturas' => '/faturas',
            ],
            'updated_at' => PlatformSetting::query()
                ->whereIn('key', [
                    self::KEY_ENABLED,
                    self::KEY_TYPE,
                    self::KEY_SYSTEM_NAME,
                    self::KEY_VERSION,
                    self::KEY_TOKEN,
                ])
                ->max('updated_at'),
        ];
    }

    /**
     * @param  array{enabled?: bool, connector_type?: string, system_name?: string, version?: string}  $data
     */
    public function update(array $data): void
    {
        if (array_key_exists('enabled', $data)) {
            PlatformSetting::set(self::KEY_ENABLED, $data['enabled'] ? '1' : '0');
        }

        if (isset($data['connector_type'])) {
            PlatformSetting::set(self::KEY_TYPE, $data['connector_type']);
        }

        if (isset($data['system_name'])) {
            PlatformSetting::set(self::KEY_SYSTEM_NAME, $data['system_name']);
        }

        if (isset($data['version'])) {
            PlatformSetting::set(self::KEY_VERSION, $data['version']);
        }
    }

    public function regenerateToken(): string
    {
        $plain = Str::random(48);
        PlatformSetting::set(self::KEY_TOKEN, Crypt::encryptString($plain));
        PlatformSetting::set(self::KEY_ENABLED, '1');

        return $plain;
    }

    public function storeToken(string $plainToken): void
    {
        PlatformSetting::set(self::KEY_TOKEN, Crypt::encryptString($plainToken));
    }

    /**
     * Garante chaves padrão em platform_settings (fonte única de configuração).
     */
    public function ensureDefaults(): void
    {
        if (self::$defaultsEnsured || ! Schema::hasTable('platform_settings')) {
            return;
        }

        self::$defaultsEnsured = true;

        $this->setIfMissing(self::KEY_TYPE, self::DEFAULT_TYPE);
        $this->setIfMissing(self::KEY_VERSION, self::DEFAULT_VERSION);
        $this->setIfMissing(self::KEY_SYSTEM_NAME, (string) (PlatformSetting::get('product_name') ?: config('app.name', 'Gestgo')));
        $this->setIfMissing(self::KEY_ENABLED, '0');
    }

    private function setIfMissing(string $key, mixed $value): void
    {
        $current = PlatformSetting::get($key);
        if ($current !== null && $current !== '') {
            return;
        }

        PlatformSetting::set($key, $value);
    }
}
