<?php

namespace App\Services;

use App\Models\PlatformSetting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

class ResendConfigService
{
    public const KEY_API_KEY = 'resend_api_key';

    public const KEY_MAILER = 'mail_mailer';

    public const KEY_FROM_ADDRESS = 'mail_from_address';

    public const KEY_FROM_NAME = 'mail_from_name';

    public const KEY_SUPPORT_EMAIL = 'mail_support_email';

    public const KEY_LOGO_URL = 'mail_logo_url';

    public const KEY_LOGO_PATH = 'mail_logo_path';

    public const KEY_SIGNATURE_PHOTO_PATH = 'mail_signature_photo_path';

    public const KEY_SENDER_NAME = 'mail_sender_name';

    public const KEY_SENDER_ROLE = 'mail_sender_role';

    public const KEY_WHATSAPP_NUMBER = 'mail_whatsapp_number';

    public const KEY_PRIMARY_COLOR = 'mail_primary_color';

    public const KEY_PRODUCT_NAME = 'mail_product_name';

    private static bool $defaultsEnsured = false;

    public function getMailer(): string
    {
        $this->ensureDefaults();

        $value = PlatformSetting::get(self::KEY_MAILER);
        if (is_string($value) && in_array($value, ['resend', 'log'], true)) {
            return $value;
        }

        $env = env('MAIL_MAILER', 'log');

        return in_array($env, ['resend', 'log'], true) ? $env : 'log';
    }

    public function getApiKey(): ?string
    {
        $this->ensureDefaults();

        return $this->decryptSetting(self::KEY_API_KEY)
            ?? $this->normalizeEnvSecret(env('RESEND_API_KEY'));
    }

    public function getFromAddress(): string
    {
        $this->ensureDefaults();

        $value = PlatformSetting::get(self::KEY_FROM_ADDRESS);
        if (is_string($value) && $value !== '') {
            return $value;
        }

        return (string) (env('MAIL_FROM_ADDRESS') ?: 'hello@example.com');
    }

    public function getFromName(): string
    {
        $this->ensureDefaults();

        $value = PlatformSetting::get(self::KEY_FROM_NAME);
        if (is_string($value) && $value !== '') {
            return $value;
        }

        $env = env('MAIL_FROM_NAME');
        if (is_string($env) && $env !== '') {
            return $env;
        }

        $productName = PlatformSetting::get('product_name');

        return is_string($productName) && $productName !== ''
            ? $productName
            : (string) config('app.name', 'Gestgo');
    }

    public function getSupportEmail(): ?string
    {
        $this->ensureDefaults();

        $value = PlatformSetting::get(self::KEY_SUPPORT_EMAIL);
        if (is_string($value) && $value !== '') {
            return $value;
        }

        return $this->normalizeEnvSecret(env('MAIL_SUPPORT_EMAIL'));
    }

    public function getLogoUrl(): ?string
    {
        $this->ensureDefaults();

        $value = PlatformSetting::get(self::KEY_LOGO_URL);
        if (is_string($value) && $value !== '') {
            return $value;
        }

        return $this->normalizeEnvSecret(env('MAIL_LOGO_URL'));
    }

    public function getLogoPath(): ?string
    {
        $this->ensureDefaults();

        $value = PlatformSetting::get(self::KEY_LOGO_PATH);

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function getSignaturePhotoPath(): ?string
    {
        $this->ensureDefaults();

        $value = PlatformSetting::get(self::KEY_SIGNATURE_PHOTO_PATH);

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function getSenderName(): ?string
    {
        $this->ensureDefaults();

        $value = PlatformSetting::get(self::KEY_SENDER_NAME);

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function getSenderRole(): ?string
    {
        $this->ensureDefaults();

        $value = PlatformSetting::get(self::KEY_SENDER_ROLE);

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function getWhatsappNumber(): ?string
    {
        $this->ensureDefaults();

        $value = PlatformSetting::get(self::KEY_WHATSAPP_NUMBER);

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function getPrimaryColor(): string
    {
        $this->ensureDefaults();

        $value = PlatformSetting::get(self::KEY_PRIMARY_COLOR);
        if (is_string($value) && $value !== '') {
            return $value;
        }

        return (string) (env('MAIL_PRIMARY_COLOR') ?: '#1e40af');
    }

    public function getProductName(): ?string
    {
        $this->ensureDefaults();

        $value = PlatformSetting::get(self::KEY_PRODUCT_NAME);
        if (is_string($value) && $value !== '') {
            return $value;
        }

        return $this->normalizeEnvSecret(env('MAIL_PRODUCT_NAME'));
    }

    public function isConfigured(): bool
    {
        if ($this->getMailer() === 'log') {
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
        $branding = app(PlatformEmailBrandingService::class);

        return [
            'mailer' => $this->getMailer(),
            'from_address' => $this->getFromAddress(),
            'from_name' => $this->getFromName(),
            'support_email' => $this->getSupportEmail(),
            'logo_url' => $this->getLogoUrl(),
            'logo_path' => $this->getLogoPath(),
            'logo_preview_url' => $branding->getEffectiveLogoUrl(60),
            'signature_photo_path' => $this->getSignaturePhotoPath(),
            'signature_photo_preview_url' => $branding->getSignaturePhotoUrl(60),
            'sender_name' => $this->getSenderName(),
            'sender_role' => $this->getSenderRole(),
            'whatsapp_number' => $this->getWhatsappNumber(),
            'primary_color' => $this->getPrimaryColor(),
            'product_name' => $this->getProductName(),
            'configured' => $this->isConfigured(),
            'api_key_preview' => $this->getApiKeyPreview(),
        ];
    }

    /**
     * @param  array{
     *     mailer?: string,
     *     api_key?: string|null,
     *     from_address?: string,
     *     from_name?: string,
     *     support_email?: string|null,
     *     logo_url?: string|null,
     *     sender_name?: string|null,
     *     sender_role?: string|null,
     *     whatsapp_number?: string|null,
     *     primary_color?: string|null,
     *     product_name?: string|null
     * }  $data
     */
    public function update(array $data): void
    {
        if (isset($data['mailer'])) {
            PlatformSetting::set(self::KEY_MAILER, $data['mailer']);
        }

        if (array_key_exists('api_key', $data)) {
            $plain = is_string($data['api_key']) ? trim($data['api_key']) : '';
            if ($plain !== '') {
                PlatformSetting::set(self::KEY_API_KEY, Crypt::encryptString($plain));
            }
        }

        if (isset($data['from_address'])) {
            PlatformSetting::set(self::KEY_FROM_ADDRESS, trim((string) $data['from_address']));
        }

        if (isset($data['from_name'])) {
            PlatformSetting::set(self::KEY_FROM_NAME, trim((string) $data['from_name']));
        }

        if (array_key_exists('support_email', $data)) {
            $email = is_string($data['support_email']) ? trim($data['support_email']) : '';
            PlatformSetting::set(self::KEY_SUPPORT_EMAIL, $email);
        }

        if (array_key_exists('logo_url', $data)) {
            $url = is_string($data['logo_url']) ? trim($data['logo_url']) : '';
            PlatformSetting::set(self::KEY_LOGO_URL, $url);
        }

        if (array_key_exists('sender_name', $data)) {
            $name = is_string($data['sender_name']) ? trim($data['sender_name']) : '';
            PlatformSetting::set(self::KEY_SENDER_NAME, $name);
        }

        if (array_key_exists('sender_role', $data)) {
            $role = is_string($data['sender_role']) ? trim($data['sender_role']) : '';
            PlatformSetting::set(self::KEY_SENDER_ROLE, $role);
        }

        if (array_key_exists('whatsapp_number', $data)) {
            $whatsapp = is_string($data['whatsapp_number']) ? trim($data['whatsapp_number']) : '';
            PlatformSetting::set(self::KEY_WHATSAPP_NUMBER, $whatsapp);
        }

        if (array_key_exists('primary_color', $data)) {
            $color = is_string($data['primary_color']) ? trim($data['primary_color']) : '';
            PlatformSetting::set(self::KEY_PRIMARY_COLOR, $color !== '' ? $color : '#1e40af');
        }

        if (array_key_exists('product_name', $data)) {
            $name = is_string($data['product_name']) ? trim($data['product_name']) : '';
            PlatformSetting::set(self::KEY_PRODUCT_NAME, $name);
        }
    }

    public function mergeIntoConfig(): void
    {
        if (! Schema::hasTable('platform_settings')) {
            return;
        }

        $branding = app(PlatformEmailBrandingService::class);

        Config::set('services.resend.key', $this->getApiKey() ?? '');
        Config::set('mail.default', $this->getMailer());
        Config::set('mail.from.address', $this->getFromAddress());
        Config::set('mail.from.name', $this->getFromName());
        Config::set('mail.branding', array_merge(Config::get('mail.branding', []), array_filter([
            'product_name' => $this->getProductName(),
            'logo_url' => $branding->getEffectiveLogoUrl(10080),
            'primary_color' => $this->getPrimaryColor(),
            'support_email' => $this->getSupportEmail(),
            'signature_photo_url' => $branding->getSignaturePhotoUrl(10080),
            'sender_name' => $this->getSenderName() ?: $this->getFromName(),
            'sender_role' => $this->getSenderRole(),
            'sender_email' => $this->getSupportEmail() ?: $this->getFromAddress(),
            'whatsapp_number' => $this->getWhatsappNumber(),
        ], fn ($value) => $value !== null && $value !== '')));
    }

    public function ensureDefaults(): void
    {
        if (self::$defaultsEnsured || ! Schema::hasTable('platform_settings')) {
            return;
        }

        self::$defaultsEnsured = true;

        $this->setIfMissing(self::KEY_MAILER, (string) (env('MAIL_MAILER') ?: 'log'));
        $this->setIfMissing(self::KEY_FROM_ADDRESS, (string) (env('MAIL_FROM_ADDRESS') ?: 'hello@example.com'));
        $this->setIfMissing(
            self::KEY_FROM_NAME,
            (string) (env('MAIL_FROM_NAME') ?: (PlatformSetting::get('product_name') ?: config('app.name', 'Gestgo')))
        );
        $this->setIfMissing(self::KEY_PRIMARY_COLOR, (string) (env('MAIL_PRIMARY_COLOR') ?: '#1e40af'));
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
