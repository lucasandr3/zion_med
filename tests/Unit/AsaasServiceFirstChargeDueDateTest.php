<?php

namespace Tests\Unit;

use App\Models\Clinic;
use App\Services\AsaasService;
use Tests\TestCase;

class AsaasServiceFirstChargeDueDateTest extends TestCase
{
    public function test_during_trial_returns_trial_end_plus_grace_days(): void
    {
        config(['asaas.grace_days' => 7]);
        $trialEnd = now()->addDays(14)->startOfDay();
        $clinic = Clinic::make([
            'subscription_status' => 'trial',
            'trial_ends_at' => $trialEnd,
        ]);
        $service = app(AsaasService::class);

        $due = $service->firstChargeDueDateForClinic($clinic);

        $this->assertSame(
            $trialEnd->copy()->addDays(7)->format('Y-m-d'),
            $due
        );
    }

    public function test_when_not_on_trial_returns_today(): void
    {
        config(['asaas.grace_days' => 7]);
        $clinic = Clinic::make([
            'subscription_status' => 'active',
            'trial_ends_at' => now()->addDays(14),
        ]);
        $service = app(AsaasService::class);

        $due = $service->firstChargeDueDateForClinic($clinic);

        $this->assertSame(now()->format('Y-m-d'), $due);
    }

    public function test_when_trial_expired_returns_today(): void
    {
        config(['asaas.grace_days' => 7]);
        $clinic = Clinic::make([
            'subscription_status' => 'trial',
            'trial_ends_at' => now()->subDays(10),
        ]);
        $service = app(AsaasService::class);

        $due = $service->firstChargeDueDateForClinic($clinic);

        $this->assertSame(now()->format('Y-m-d'), $due);
    }
}
