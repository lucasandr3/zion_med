<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Payment;
use App\Models\Subscription;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BillingCancelRemovesPendingPaymentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_cancel_subscription_removes_unpaid_local_payments(): void
    {
        $user = $this->qaClinicOwnerUser();
        $org = $user->clinic;
        $org->update([
            'trial_ends_at' => now()->addDays(10),
            'subscription_status' => 'active',
            'billing_status' => 'ok',
            'plan_key' => 'executive',
        ]);

        $sub = Subscription::query()->create([
            'organization_id' => $org->id,
            'asaas_subscription_id' => 'sub_cancel_test_1',
            'plan_key' => 'executive',
            'status' => 'active',
            'next_due_date' => now()->addMonth(),
        ]);

        Payment::query()->create([
            'organization_id' => $org->id,
            'subscription_id' => $sub->id,
            'asaas_payment_id' => 'pay_cancel_pending_1',
            'status' => 'PENDING',
            'due_date' => now()->addWeek()->format('Y-m-d'),
            'value' => 247,
            'bank_slip_url' => 'https://example.test/boleto',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/billing/subscriptions/{$sub->id}/cancel");

        $response->assertOk();
        $this->assertSame(0, Payment::query()->where('asaas_payment_id', 'pay_cancel_pending_1')->count());
    }

    public function test_cancel_subscription_keeps_confirmed_payment_record(): void
    {
        $user = $this->qaClinicOwnerUser();
        $org = $user->clinic;
        $org->update([
            'trial_ends_at' => now()->subDay(),
            'subscription_status' => 'active',
            'billing_status' => 'ok',
            'plan_key' => 'executive',
        ]);

        $sub = Subscription::query()->create([
            'organization_id' => $org->id,
            'asaas_subscription_id' => 'sub_cancel_test_2',
            'plan_key' => 'executive',
            'status' => 'active',
            'next_due_date' => now()->addMonth(),
        ]);

        Payment::query()->create([
            'organization_id' => $org->id,
            'subscription_id' => $sub->id,
            'asaas_payment_id' => 'pay_cancel_received_1',
            'status' => 'RECEIVED',
            'due_date' => now()->subWeek()->format('Y-m-d'),
            'paid_at' => now()->subWeek(),
            'value' => 247,
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/billing/subscriptions/{$sub->id}/cancel")->assertOk();

        $this->assertDatabaseHas('payments', ['asaas_payment_id' => 'pay_cancel_received_1', 'status' => 'RECEIVED']);
    }
}
