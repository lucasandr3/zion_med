<?php

namespace Tests\Feature;

use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\ClinicSeeder::class);
    }

    public function test_user_on_trial_can_access_dashboard(): void
    {
        $user = $this->qaClinicOwnerUser();
        $clinic = $user->clinic;
        $clinic->update([
            'trial_ends_at' => now()->addDays(14),
            'subscription_status' => 'trial',
            'billing_status' => 'ok',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);
    }

    public function test_expired_trial_without_subscription_redirects_to_billing(): void
    {
        $user = $this->qaClinicOwnerUser();
        $clinic = $user->clinic;
        $clinic->update([
            'trial_ends_at' => now()->subDay(),
            'subscription_status' => 'trial',
            'billing_status' => 'ok',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertRedirect(route('billing.index'));
        $response->assertSessionHas('error');
        $clinic->refresh();
        $this->assertSame('inactive', $clinic->subscription_status);
        $this->assertSame('blocked', $clinic->billing_status);
    }

    public function test_past_due_within_grace_period_allows_access_with_warning(): void
    {
        $user = $this->qaClinicOwnerUser();
        $clinic = $user->clinic;
        $clinic->update([
            'subscription_status' => 'past_due',
            'billing_status' => 'attention',
            'grace_ends_at' => now()->addDays(5),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSessionHas('billing_warning');
    }

    public function test_past_due_after_grace_period_redirects_to_billing(): void
    {
        $user = $this->qaClinicOwnerUser();
        $clinic = $user->clinic;
        $clinic->update([
            'subscription_status' => 'past_due',
            'billing_status' => 'attention',
            'grace_ends_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertRedirect(route('billing.index'));
        $response->assertSessionHas('error');
        $clinic->refresh();
        $this->assertSame('blocked', $clinic->billing_status);
    }

    public function test_expired_trial_with_active_subscription_promotes_clinic_to_active(): void
    {
        $user = $this->qaClinicOwnerUser();
        $clinic = $user->clinic;
        $clinic->update([
            'trial_ends_at' => now()->subDay(),
            'subscription_status' => 'trial',
            'billing_status' => 'ok',
        ]);
        Subscription::create([
            'organization_id' => $clinic->id,
            'asaas_subscription_id' => 'sub_qa_mw_test',
            'plan_key' => 'executive',
            'status' => 'active',
            'next_due_date' => now()->addMonth(),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);
        $clinic->refresh();
        $this->assertSame('active', $clinic->subscription_status);
        $this->assertSame('ok', $clinic->billing_status);
    }

    public function test_billing_page_always_accessible_when_authenticated(): void
    {
        $user = $this->qaClinicOwnerUser();
        $user->clinic->update([
            'subscription_status' => 'inactive',
            'billing_status' => 'blocked',
        ]);

        $response = $this->actingAs($user)->get(route('billing.index'));
        $response->assertRedirect(route('clinica.configuracoes.edit', ['tab' => 'assinatura']));
    }
}
