<?php

namespace App\Services;

use App\Models\PlatformSetting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

class MinioConfigService
{
    public const KEY_ENDPOINT = 'minio_endpoint';

    public const KEY_ACCESS_KEY = 'minio_access_key';

    public const KEY_SECRET_KEY = 'minio_secret_key';

    public const KEY_REGION = 'minio_region';

    public const KEY_SUBMISSIONS_BUCKET = 'minio_submissions_bucket';

    public const KEY_ATTACHMENTS_BUCKET = 'minio_attachments_bucket';

    public const KEY_ASSETS_BUCKET = 'minio_assets_bucket';

    public const KEY_INVOICES_BUCKET = 'minio_invoices_bucket';

    /** @var array<string, string> */
    private const DISK_BUCKET_KEYS = [
        'minio_submissions' => self::KEY_SUBMISSIONS_BUCKET,
        'minio_attachments' => self::KEY_ATTACHMENTS_BUCKET,
        'minio_assets' => self::KEY_ASSETS_BUCKET,
        'minio_invoices' => self::KEY_INVOICES_BUCKET,
    ];

    private static bool $defaultsEnsured = false;

    public function getEndpoint(): ?string
    {
        $this->ensureDefaults();

        $value = PlatformSetting::get(self::KEY_ENDPOINT);

        return is_string($value) && $value !== ''
            ? rtrim($value, '/')
            : $this->normalizeEnvSecret(env('MINIO_ENDPOINT'));
    }

    public function getAccessKey(): ?string
    {
        $this->ensureDefaults();

        $value = PlatformSetting::get(self::KEY_ACCESS_KEY);

        return is_string($value) && $value !== ''
            ? $value
            : $this->normalizeEnvSecret(env('MINIO_ACCESS_KEY'));
    }

    public function getSecretKey(): ?string
    {
        $this->ensureDefaults();

        return $this->decryptSetting(self::KEY_SECRET_KEY)
            ?? $this->normalizeEnvSecret(env('MINIO_SECRET_KEY'));
    }

    public function getRegion(): string
    {
        $this->ensureDefaults();

        $value = PlatformSetting::get(self::KEY_REGION);
        if (is_string($value) && $value !== '') {
            return $value;
        }

        return (string) (env('MINIO_REGION') ?: 'us-east-1');
    }

    public function getSubmissionsBucket(): ?string
    {
        return $this->getBucket(self::KEY_SUBMISSIONS_BUCKET, 'MINIO_SUBMISSIONS_BUCKET');
    }

    public function getAttachmentsBucket(): ?string
    {
        return $this->getBucket(self::KEY_ATTACHMENTS_BUCKET, 'MINIO_ATTACHMENTS_BUCKET');
    }

    public function getAssetsBucket(): ?string
    {
        return $this->getBucket(self::KEY_ASSETS_BUCKET, 'MINIO_ASSETS_BUCKET');
    }

    public function getInvoicesBucket(): ?string
    {
        return $this->getBucket(self::KEY_INVOICES_BUCKET, 'MINIO_INVOICES_BUCKET');
    }

    public function isConfigured(): bool
    {
        return $this->getEndpoint() !== null
            && $this->getAccessKey() !== null
            && $this->getSecretKey() !== null;
    }

    public function getSecretKeyPreview(): ?string
    {
        $secret = $this->getSecretKey();
        if ($secret === null || $secret === '') {
            return null;
        }

        if (strlen($secret) <= 8) {
            return '••••••••';
        }

        return substr($secret, 0, 4).'••••'.substr($secret, -4);
    }

    /**
     * @return array<string, mixed>
     */
    public function toSettingsPayload(): array
    {
        return [
            'endpoint' => $this->getEndpoint(),
            'access_key' => $this->getAccessKey(),
            'region' => $this->getRegion(),
            'submissions_bucket' => $this->getSubmissionsBucket(),
            'attachments_bucket' => $this->getAttachmentsBucket(),
            'assets_bucket' => $this->getAssetsBucket(),
            'invoices_bucket' => $this->getInvoicesBucket(),
            'configured' => $this->isConfigured(),
            'secret_key_preview' => $this->getSecretKeyPreview(),
        ];
    }

    /**
     * @param  array{
     *     endpoint?: string,
     *     access_key?: string,
     *     secret_key?: string|null,
     *     region?: string,
     *     submissions_bucket?: string,
     *     attachments_bucket?: string,
     *     assets_bucket?: string,
     *     invoices_bucket?: string
     * }  $data
     */
    public function update(array $data): void
    {
        if (isset($data['endpoint'])) {
            PlatformSetting::set(self::KEY_ENDPOINT, rtrim((string) $data['endpoint'], '/'));
        }

        if (isset($data['access_key'])) {
            PlatformSetting::set(self::KEY_ACCESS_KEY, trim((string) $data['access_key']));
        }

        if (array_key_exists('secret_key', $data)) {
            $plain = is_string($data['secret_key']) ? trim($data['secret_key']) : '';
            if ($plain !== '') {
                PlatformSetting::set(self::KEY_SECRET_KEY, Crypt::encryptString($plain));
            }
        }

        if (isset($data['region'])) {
            PlatformSetting::set(self::KEY_REGION, trim((string) $data['region']));
        }

        $bucketMap = [
            'submissions_bucket' => self::KEY_SUBMISSIONS_BUCKET,
            'attachments_bucket' => self::KEY_ATTACHMENTS_BUCKET,
            'assets_bucket' => self::KEY_ASSETS_BUCKET,
            'invoices_bucket' => self::KEY_INVOICES_BUCKET,
        ];

        foreach ($bucketMap as $inputKey => $settingKey) {
            if (isset($data[$inputKey])) {
                PlatformSetting::set($settingKey, trim((string) $data[$inputKey]));
            }
        }
    }

    public function applyFilesystemConfig(): void
    {
        if (! Schema::hasTable('platform_settings')) {
            return;
        }

        $endpoint = $this->getEndpoint();
        $accessKey = $this->getAccessKey();
        $secretKey = $this->getSecretKey();
        $region = $this->getRegion();

        foreach (self::DISK_BUCKET_KEYS as $disk => $bucketKey) {
            $bucket = PlatformSetting::get($bucketKey);
            if (! is_string($bucket) || $bucket === '') {
                $bucket = $this->envBucketForDisk($disk);
            }

            $current = Config::get("filesystems.disks.{$disk}", []);

            Config::set("filesystems.disks.{$disk}", array_merge($current, array_filter([
                'endpoint' => $endpoint,
                'key' => $accessKey,
                'secret' => $secretKey,
                'region' => $region,
                'bucket' => $bucket,
            ], fn ($value) => $value !== null && $value !== '')));
        }
    }

    public function ensureDefaults(): void
    {
        if (self::$defaultsEnsured || ! Schema::hasTable('platform_settings')) {
            return;
        }

        self::$defaultsEnsured = true;

        $this->setIfMissing(self::KEY_REGION, (string) (env('MINIO_REGION') ?: 'us-east-1'));
        $this->setIfMissing(self::KEY_SUBMISSIONS_BUCKET, (string) env('MINIO_SUBMISSIONS_BUCKET', 'gestgo-submissions'));
        $this->setIfMissing(self::KEY_ATTACHMENTS_BUCKET, (string) env('MINIO_ATTACHMENTS_BUCKET', 'gestgo-attachments'));
        $this->setIfMissing(self::KEY_ASSETS_BUCKET, (string) env('MINIO_ASSETS_BUCKET', 'gestgo-assets'));
        $this->setIfMissing(self::KEY_INVOICES_BUCKET, (string) env('MINIO_INVOICES_BUCKET', 'gestgo-invoices'));
    }

    private function getBucket(string $settingKey, string $envKey): ?string
    {
        $this->ensureDefaults();

        $value = PlatformSetting::get($settingKey);
        if (is_string($value) && $value !== '') {
            return $value;
        }

        return $this->normalizeEnvSecret(env($envKey));
    }

    private function envBucketForDisk(string $disk): ?string
    {
        return match ($disk) {
            'minio_submissions' => $this->normalizeEnvSecret(env('MINIO_SUBMISSIONS_BUCKET')),
            'minio_attachments' => $this->normalizeEnvSecret(env('MINIO_ATTACHMENTS_BUCKET')),
            'minio_assets' => $this->normalizeEnvSecret(env('MINIO_ASSETS_BUCKET')),
            'minio_invoices' => $this->normalizeEnvSecret(env('MINIO_INVOICES_BUCKET')),
            default => null,
        };
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
}
