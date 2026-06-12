<?php

use App\Models\PlatformSetting;
use App\Services\AsaasConfigService;
use App\Services\MinioConfigService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('platform_settings')) {
            return;
        }

        $setIfMissing = function (string $key, mixed $value): void {
            $current = PlatformSetting::get($key);
            if ($current !== null && $current !== '') {
                return;
            }
            PlatformSetting::set($key, $value);
        };

        $asaasBaseUrl = env('ASAAS_BASE_URL');
        if (is_string($asaasBaseUrl) && $asaasBaseUrl !== '') {
            $setIfMissing(AsaasConfigService::KEY_BASE_URL, rtrim($asaasBaseUrl, '/'));
        }

        $asaasApiKey = env('ASAAS_API_KEY');
        if (is_string($asaasApiKey) && $asaasApiKey !== '' && ! PlatformSetting::get(AsaasConfigService::KEY_API_KEY)) {
            PlatformSetting::set(AsaasConfigService::KEY_API_KEY, Crypt::encryptString($asaasApiKey));
        }

        $asaasWebhook = env('ASAAS_WEBHOOK_SECRET');
        if (is_string($asaasWebhook) && $asaasWebhook !== '' && ! PlatformSetting::get(AsaasConfigService::KEY_WEBHOOK_SECRET)) {
            PlatformSetting::set(AsaasConfigService::KEY_WEBHOOK_SECRET, Crypt::encryptString($asaasWebhook));
        }

        $minioEndpoint = env('MINIO_ENDPOINT');
        if (is_string($minioEndpoint) && $minioEndpoint !== '') {
            $setIfMissing(MinioConfigService::KEY_ENDPOINT, rtrim($minioEndpoint, '/'));
        }

        $minioAccessKey = env('MINIO_ACCESS_KEY');
        if (is_string($minioAccessKey) && $minioAccessKey !== '') {
            $setIfMissing(MinioConfigService::KEY_ACCESS_KEY, $minioAccessKey);
        }

        $minioSecretKey = env('MINIO_SECRET_KEY');
        if (is_string($minioSecretKey) && $minioSecretKey !== '' && ! PlatformSetting::get(MinioConfigService::KEY_SECRET_KEY)) {
            PlatformSetting::set(MinioConfigService::KEY_SECRET_KEY, Crypt::encryptString($minioSecretKey));
        }

        $minioRegion = env('MINIO_REGION');
        if (is_string($minioRegion) && $minioRegion !== '') {
            $setIfMissing(MinioConfigService::KEY_REGION, $minioRegion);
        }

        $bucketMap = [
            MinioConfigService::KEY_SUBMISSIONS_BUCKET => 'MINIO_SUBMISSIONS_BUCKET',
            MinioConfigService::KEY_ATTACHMENTS_BUCKET => 'MINIO_ATTACHMENTS_BUCKET',
            MinioConfigService::KEY_ASSETS_BUCKET => 'MINIO_ASSETS_BUCKET',
            MinioConfigService::KEY_INVOICES_BUCKET => 'MINIO_INVOICES_BUCKET',
        ];

        foreach ($bucketMap as $settingKey => $envKey) {
            $value = env($envKey);
            if (is_string($value) && $value !== '') {
                $setIfMissing($settingKey, $value);
            }
        }
    }

    public function down(): void
    {
        // Configurações permanecem no banco; não revertemos dados de integração.
    }
};
