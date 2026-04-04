<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Services\AsaasService;
use Tests\TestCase;

class AsaasServiceFirstChargeDueDateTest extends TestCase
{
    public function test_during_trial_returns_trial_end_plus_grace_days(): void
    {
        config(['asaas.grace_days' => 7]);
        $trialEnd = now()->addDays(14)->startOfDay();
        $organization = Organization::make([
            'subscription_status' => 'trial',
            'trial_ends_at' => $trialEnd,
        ]);
        $service = app(AsaasService::class);

        $due = $service->firstChargeDueDateForOrganization($organization);

        $this->assertSame(
            $trialEnd->copy()->addDays(7)->format('Y-m-d'),
            $due
        );
    }

    public function test_when_no_trial_end_configured_returns_today(): void
    {
        config(['asaas.grace_days' => 7]);
        $organization = Organization::make([
            'subscription_status' => 'active',
            'trial_ends_at' => null,
        ]);
        $service = app(AsaasService::class);

        $due = $service->firstChargeDueDateForOrganization($organization);

        $this->assertSame(now()->format('Y-m-d'), $due);
    }

    public function test_when_active_but_trial_window_still_open_uses_trial_end_plus_grace(): void
    {
        config(['asaas.grace_days' => 7]);
        $trialEnd = now()->addDays(14)->startOfDay();
        $organization = Organization::make([
            'subscription_status' => 'active',
            'trial_ends_at' => $trialEnd,
        ]);
        $service = app(AsaasService::class);

        $due = $service->firstChargeDueDateForOrganization($organization);

        $this->assertSame($trialEnd->copy()->addDays(7)->format('Y-m-d'), $due);
    }

    public function test_when_trial_expired_returns_today(): void
    {
        config(['asaas.grace_days' => 7]);
        $organization = Organization::make([
            'subscription_status' => 'trial',
            'trial_ends_at' => now()->subDays(10),
        ]);
        $service = app(AsaasService::class);

        $due = $service->firstChargeDueDateForOrganization($organization);

        $this->assertSame(now()->format('Y-m-d'), $due);
    }
}
