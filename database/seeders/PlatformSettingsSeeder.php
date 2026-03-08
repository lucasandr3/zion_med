<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\PlatformSetting;
use Illuminate\Database\Seeder;

class PlatformSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'product_name' => config('asaas.product_name', 'ZionMed'),
            'trial_days' => (string) (config('asaas.trial_days') ?? 14),
            'grace_days' => (string) (config('asaas.grace_days') ?? 7),
            'block_mode' => config('asaas.block_mode', 'soft'),
            'multi_empresa_plan' => config('asaas.multi_empresa_plan', 'enterprise'),
        ];

        foreach ($defaults as $key => $value) {
            PlatformSetting::set($key, $value);
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
                'sort_order' => $sortOrder++,
                'is_active' => true,
            ]);
        }
    }
}
