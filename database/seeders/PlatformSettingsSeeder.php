<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\PlatformSetting;
use App\Services\BusinessHubConfigService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;

class PlatformSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'product_name' => config('asaas.product_name', 'Gestgo'),
            'trial_days' => (string) (config('asaas.trial_days') ?? 14),
            'grace_days' => (string) (config('asaas.grace_days') ?? 7),
            'block_mode' => config('asaas.block_mode', 'soft'),
            'multi_empresa_plan' => config('asaas.multi_empresa_plan', 'enterprise'),
            'service_status' => 'operational',
            'service_status_severity' => 'none',
            'service_status_message' => '',
            'service_status_components' => json_encode([
                'platform' => 'operational',
                'api' => 'operational',
                'forms' => 'operational',
                'billing' => 'operational',
            ]),
            BusinessHubConfigService::KEY_ENABLED => '0',
            BusinessHubConfigService::KEY_TYPE => BusinessHubConfigService::DEFAULT_TYPE,
            BusinessHubConfigService::KEY_SYSTEM_NAME => config('asaas.product_name', 'Gestgo'),
            BusinessHubConfigService::KEY_VERSION => BusinessHubConfigService::DEFAULT_VERSION,
        ];

        foreach ($defaults as $key => $value) {
            $this->setIfMissing($key, $value);
        }

        $legacyToken = env('BUSINESS_HUB_CONNECTOR_TOKEN');
        if (
            is_string($legacyToken)
            && $legacyToken !== ''
            && ! PlatformSetting::get(BusinessHubConfigService::KEY_TOKEN)
        ) {
            PlatformSetting::set(BusinessHubConfigService::KEY_TOKEN, Crypt::encryptString($legacyToken));
            PlatformSetting::set(BusinessHubConfigService::KEY_ENABLED, '1');
        }

        if (Plan::count() > 0) {
            return;
        }

        $plansFromConfig = config('asaas.plans', []);
        $sortOrder = 0;
        foreach ($plansFromConfig as $key => $plan) {
            Plan::create([
                'key' => $key,
                'name' => $plan['name'] ?? $key,
                'value' => $plan['value'] ?? 0,
                'description' => $plan['description'] ?? null,
                'max_users' => isset($plan['max_users']) ? (int) $plan['max_users'] : null,
                'max_organizations_per_tenant' => isset($plan['max_organizations_per_tenant'])
                    ? (int) $plan['max_organizations_per_tenant']
                    : null,
                'sort_order' => $sortOrder++,
                'is_active' => true,
            ]);
        }
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
