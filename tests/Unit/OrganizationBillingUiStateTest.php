<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\Payment;
use App\Models\Subscription;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationBillingUiStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_after_trial_with_active_sub_and_no_payment_ui_favors_checkout_not_managed_card(): void
    {
        $this->seed(DatabaseSeeder::class);
        $org = Organization::query()->where('slug', 'clinica-qa-gestgo')->firstOrFail();

        Subscription::query()->create([
            'organization_id' => $org->id,
            'asaas_subscription_id' => 'sub_test_pending',
            'plan_key' => 'executive',
            'status' => 'active',
            'next_due_date' => now()->addMonth(),
        ]);

        $org->update([
            'trial_ends_at' => now()->subDay(),
            'subscription_status' => 'inactive',
            'billing_status' => 'blocked',
        ]);
        $org->refresh();

        $this->assertTrue($org->isAwaitingFirstBillingPayment());
        $this->assertFalse($org->billingUiShowsManagedActiveSubscription());
        $this->assertTrue($org->billingUiShowsPlanSelection());

        $state = $org->billingUiState();
        $this->assertFalse($state['show_managed_subscription_card']);
        $this->assertTrue($state['show_pending_first_payment']);
        $this->assertTrue($state['show_plan_selection']);
        $this->assertNotNull($state['pending_first_payment_message']);
    }

    public function test_during_trial_with_sub_shows_managed_card_not_plan_selection(): void
    {
        $this->seed(DatabaseSeeder::class);
        $org = Organization::query()->where('slug', 'clinica-qa-gestgo')->firstOrFail();

        Subscription::query()->create([
            'organization_id' => $org->id,
            'asaas_subscription_id' => 'sub_test_trial',
            'plan_key' => 'executive',
            'status' => 'active',
            'next_due_date' => now()->addMonth(),
        ]);

        $org->update([
            'trial_ends_at' => now()->addDays(5),
            'subscription_status' => 'active',
            'billing_status' => 'ok',
        ]);
        $org->refresh();

        $this->assertFalse($org->isAwaitingFirstBillingPayment());
        $this->assertTrue($org->billingUiShowsManagedActiveSubscription());
        $this->assertFalse($org->billingUiShowsPlanSelection());
    }

    public function test_confirmed_payment_shows_managed_when_sub_exists(): void
    {
        $this->seed(DatabaseSeeder::class);
        $org = Organization::query()->where('slug', 'clinica-qa-gestgo')->firstOrFail();

        $sub = Subscription::query()->create([
            'organization_id' => $org->id,
            'asaas_subscription_id' => 'sub_test_paid',
            'plan_key' => 'executive',
            'status' => 'active',
            'next_due_date' => now()->addMonth(),
        ]);

        Payment::query()->create([
            'organization_id' => $org->id,
            'subscription_id' => $sub->id,
            'asaas_payment_id' => 'pay_test_1',
            'status' => 'RECEIVED',
            'due_date' => now()->subWeek()->format('Y-m-d'),
            'paid_at' => now()->subWeek(),
            'value' => 247,
            'bank_slip_url' => null,
        ]);

        $org->update([
            'trial_ends_at' => now()->subDay(),
            'subscription_status' => 'active',
            'billing_status' => 'ok',
        ]);
        $org->refresh();

        $this->assertFalse($org->isAwaitingFirstBillingPayment());
        $this->assertTrue($org->billingUiShowsManagedActiveSubscription());
        $this->assertFalse($org->billingUiShowsPlanSelection());
    }
}
