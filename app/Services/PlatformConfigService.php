<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

class PlatformConfigService
{
    /**
     * Mescla configurações do banco (platform_settings + plans) com config/asaas e .env,
     * e define o resultado em Config::get('asaas') para que config('asaas.xxx') continue funcionando.
     * Secrets (base_url, api_key, webhook_secret) vêm sempre de config/.env.
     */
    public static function mergeIntoConfig(): void
    {
        if (! Schema::hasTable('platform_settings') || ! Schema::hasTable('plans')) {
            return;
        }

        $fileConfig = Config::get('asaas', []);
        $settings = PlatformSetting::getAllCached();
        $dbPlans = Plan::getForConfigCached();

        $merged = array_merge($fileConfig, [
            'product_name' => $settings['product_name'] ?? $fileConfig['product_name'] ?? 'ZionMed',
            'trial_days' => isset($settings['trial_days']) ? (int) $settings['trial_days'] : ($fileConfig['trial_days'] ?? 14),
            'grace_days' => isset($settings['grace_days']) ? (int) $settings['grace_days'] : ($fileConfig['grace_days'] ?? 7),
            'block_mode' => $settings['block_mode'] ?? $fileConfig['block_mode'] ?? 'soft',
            'multi_empresa_plan' => $settings['multi_empresa_plan'] ?? $fileConfig['multi_empresa_plan'] ?? 'enterprise',
            'plans' => ! empty($dbPlans) ? $dbPlans : ($fileConfig['plans'] ?? []),
        ]);

        Config::set('asaas', $merged);
    }
}
