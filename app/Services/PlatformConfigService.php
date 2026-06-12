<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

class PlatformConfigService
{
    /**
     * Mescla configurações do banco (platform_settings + plans) com config/asaas,
     * e define o resultado em Config::get('asaas') para que config('asaas.xxx') continue funcionando.
     * Credenciais ASAAS e MinIO vêm de platform_settings (com fallback legado em .env).
     */
    public static function mergeIntoConfig(): void
    {
        if (! Schema::hasTable('platform_settings')) {
            return;
        }

        $asaasConfig = app(AsaasConfigService::class);
        $minioConfig = app(MinioConfigService::class);
        $resendConfig = app(ResendConfigService::class);

        $asaasConfig->mergeIntoConfig();
        $minioConfig->applyFilesystemConfig();
        $resendConfig->mergeIntoConfig();

        if (! Schema::hasTable('plans')) {
            return;
        }

        $fileConfig = Config::get('asaas', []);
        $settings = PlatformSetting::getAllCached();
        $dbPlans = Plan::getForConfigCached();

        $merged = array_merge($fileConfig, [
            'product_name' => $settings['product_name'] ?? $fileConfig['product_name'] ?? 'Gestgo',
            'trial_days' => isset($settings['trial_days']) ? (int) $settings['trial_days'] : ($fileConfig['trial_days'] ?? 14),
            'grace_days' => isset($settings['grace_days']) ? (int) $settings['grace_days'] : ($fileConfig['grace_days'] ?? 7),
            'block_mode' => $settings['block_mode'] ?? $fileConfig['block_mode'] ?? 'soft',
            'multi_empresa_plan' => $settings['multi_empresa_plan'] ?? $fileConfig['multi_empresa_plan'] ?? 'enterprise',
            'plans' => ! empty($dbPlans) ? $dbPlans : ($fileConfig['plans'] ?? []),
        ]);

        Config::set('asaas', $merged);
    }
}
