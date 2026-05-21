<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\Role;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MeAccountDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_sole_billing_manager_deletion_cancels_gateway_subscription(): void
    {
        $user = $this->qaClinicOwnerUser();
        $org = $user->clinic;
        $org->update([
            'subscription_status' => 'active',
            'billing_status' => 'ok',
            'plan_key' => 'executive',
        ]);

        $sub = Subscription::query()->create([
            'organization_id' => $org->id,
            'asaas_subscription_id' => 'sub_me_delete_1',
            'plan_key' => 'executive',
            'status' => 'active',
            'next_due_date' => now()->addMonth(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/v1/me/account', [
            'password' => 'senha123',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.billing_canceled', true);

        $sub->refresh();
        $this->assertSame('CANCELED', $sub->status);

        $org->refresh();
        $this->assertSame('canceled', $org->subscription_status);

        $user->refresh();
        $this->assertFalse($user->active);
    }

    public function test_deletion_keeps_subscription_when_another_billing_manager_exists(): void
    {
        $owner = $this->qaClinicOwnerUser();
        $org = $owner->clinic;

        User::withoutGlobalScopes()->create([
            'organization_id' => $org->id,
            'name' => 'Outro gestor',
            'email' => 'gestor-billing@gestgo.test',
            'password' => 'senha123',
            'role' => Role::Manager->value,
            'active' => true,
            'email_verified_at' => now(),
        ]);

        $sub = Subscription::query()->create([
            'organization_id' => $org->id,
            'asaas_subscription_id' => 'sub_me_delete_2',
            'plan_key' => 'executive',
            'status' => 'active',
            'next_due_date' => now()->addMonth(),
        ]);

        Sanctum::actingAs($owner);

        $response = $this->deleteJson('/api/v1/me/account', [
            'password' => 'senha123',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.billing_canceled', false);

        $sub->refresh();
        $this->assertSame('active', $sub->status);
    }

    public function test_sole_active_user_without_billing_permission_cannot_delete(): void
    {
        $owner = $this->qaClinicOwnerUser();
        $org = $owner->clinic;

        $staff = User::withoutGlobalScopes()->create([
            'organization_id' => $org->id,
            'name' => 'Único staff',
            'email' => 'staff-only@gestgo.test',
            'password' => 'senha123',
            'role' => Role::Staff->value,
            'active' => true,
            'email_verified_at' => now(),
        ]);

        $owner->forceFill(['active' => false])->save();

        Sanctum::actingAs($staff);

        $response = $this->deleteJson('/api/v1/me/account', [
            'password' => 'senha123',
        ]);

        $response->assertStatus(422);
        $staff->refresh();
        $this->assertTrue($staff->active);
    }
}
