<?php

use App\Models\PlatformSetting;
use App\Services\BusinessHubConfigService;
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

        $setIfMissing(BusinessHubConfigService::KEY_TYPE, BusinessHubConfigService::DEFAULT_TYPE);
        $setIfMissing(BusinessHubConfigService::KEY_VERSION, BusinessHubConfigService::DEFAULT_VERSION);
        $setIfMissing(
            BusinessHubConfigService::KEY_SYSTEM_NAME,
            (string) (PlatformSetting::get('product_name') ?: config('app.name', 'Gestgo'))
        );
        $setIfMissing(BusinessHubConfigService::KEY_ENABLED, '0');

        $legacyToken = env('BUSINESS_HUB_CONNECTOR_TOKEN');
        if (
            is_string($legacyToken)
            && $legacyToken !== ''
            && ! PlatformSetting::get(BusinessHubConfigService::KEY_TOKEN)
        ) {
            PlatformSetting::set(BusinessHubConfigService::KEY_TOKEN, Crypt::encryptString($legacyToken));
            PlatformSetting::set(BusinessHubConfigService::KEY_ENABLED, '1');
        }

        $legacyType = env('BUSINESS_HUB_CONNECTOR_TYPE');
        if (is_string($legacyType) && $legacyType !== '') {
            $setIfMissing(BusinessHubConfigService::KEY_TYPE, $legacyType);
        }

        $legacyName = env('BUSINESS_HUB_SYSTEM_NAME');
        if (is_string($legacyName) && $legacyName !== '') {
            $setIfMissing(BusinessHubConfigService::KEY_SYSTEM_NAME, $legacyName);
        }

        $legacyVersion = env('BUSINESS_HUB_CONNECTOR_VERSION');
        if (is_string($legacyVersion) && $legacyVersion !== '') {
            $setIfMissing(BusinessHubConfigService::KEY_VERSION, $legacyVersion);
        }
    }

    public function down(): void
    {
        // Configurações permanecem no banco; não revertemos dados de integração.
    }
};
