<?php

use App\Models\PlatformSetting;
use App\Services\FormImportConfigService;
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

        $provider = env('FORM_IMPORT_PROVIDER');
        if (is_string($provider) && in_array($provider, ['openai', 'heuristic'], true)) {
            $setIfMissing(FormImportConfigService::KEY_PROVIDER, $provider);
        } else {
            $setIfMissing(FormImportConfigService::KEY_PROVIDER, 'openai');
        }

        $apiKey = env('OPENAI_API_KEY');
        if (is_string($apiKey) && $apiKey !== '' && ! PlatformSetting::get(FormImportConfigService::KEY_API_KEY)) {
            PlatformSetting::set(FormImportConfigService::KEY_API_KEY, Crypt::encryptString($apiKey));
        }

        $model = env('FORM_IMPORT_OPENAI_MODEL');
        if (is_string($model) && $model !== '') {
            $setIfMissing(FormImportConfigService::KEY_MODEL, $model);
        } else {
            $setIfMissing(FormImportConfigService::KEY_MODEL, 'gpt-4o-mini');
        }

        $baseUrl = env('OPENAI_BASE_URL');
        if (is_string($baseUrl) && $baseUrl !== '') {
            $setIfMissing(FormImportConfigService::KEY_BASE_URL, rtrim($baseUrl, '/'));
        } else {
            $setIfMissing(FormImportConfigService::KEY_BASE_URL, 'https://api.openai.com/v1');
        }
    }

    public function down(): void
    {
        // Configurações permanecem no banco; não revertemos dados de integração.
    }
};
