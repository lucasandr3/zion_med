<?php

namespace Tests\Feature\Api;

use App\Enums\Role;
use App\Models\Clinic;
use App\Models\FormTemplate;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TenantIsolationApiTest extends TestCase
{
    use RefreshDatabase;

    private function activateOrganization(Clinic $org): void
    {
        $org->forceFill([
            'subscription_status' => 'trial',
            'trial_ends_at' => now()->addDays(14),
            'billing_status' => 'ok',
            'plan_key' => 'solo',
        ])->save();
    }

    private function actingAsTenant(User $user, int $organizationId): static
    {
        Sanctum::actingAs($user->fresh());
        session([
            'current_clinic_id' => $organizationId,
            'current_organization_id' => $organizationId,
        ]);

        return $this;
    }

    public function test_cannot_access_other_tenant_organization_via_header(): void
    {
        $tenantA = Tenant::create(['name' => 'Tenant A', 'slug' => 'tenant-a']);
        $orgA = Clinic::create([
            'tenant_id' => $tenantA->id,
            'name' => 'Org A',
            'slug' => 'org-a',
            'notification_email' => 'a@test.com',
        ]);
        $this->activateOrganization($orgA);

        $userA = User::withoutGlobalScopes()->create([
            'organization_id' => $orgA->id,
            'name' => 'Owner A',
            'email' => 'owner-a@test.com',
            'password' => bcrypt('password'),
            'role' => Role::Owner->value,
            'active' => true,
        ]);
        $userA->forceFill(['email_verified_at' => now()])->save();

        $tenantB = Tenant::create(['name' => 'Tenant B', 'slug' => 'tenant-b']);
        $orgB = Clinic::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Org B',
            'slug' => 'org-b',
            'notification_email' => 'b@test.com',
        ]);
        $this->activateOrganization($orgB);

        FormTemplate::withoutGlobalScopes()->create([
            'organization_id' => $orgB->id,
            'name' => 'Template Secreto B',
            'is_active' => true,
            'public_enabled' => false,
        ]);

        $response = $this->actingAsTenant($userA, $orgA->id)
            ->withHeaders([
                'X-Organization-Id' => (string) $orgB->id,
                'Accept' => 'application/json',
            ])->getJson('/api/v1/templates');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name')->all();
        $this->assertNotContains('Template Secreto B', $names);
    }

    public function test_valid_header_switches_organization_within_same_tenant(): void
    {
        $tenant = Tenant::create(['name' => 'Tenant Multi', 'slug' => 'tenant-multi']);
        $org1 = Clinic::create([
            'tenant_id' => $tenant->id,
            'name' => 'Org 1',
            'slug' => 'org-1',
            'notification_email' => '1@test.com',
        ]);
        $org2 = Clinic::create([
            'tenant_id' => $tenant->id,
            'name' => 'Org 2',
            'slug' => 'org-2',
            'notification_email' => '2@test.com',
        ]);
        $this->activateOrganization($org1);
        $this->activateOrganization($org2);

        $user = User::withoutGlobalScopes()->create([
            'organization_id' => $org1->id,
            'name' => 'Owner Multi',
            'email' => 'owner-multi@test.com',
            'password' => bcrypt('password'),
            'role' => Role::Owner->value,
            'active' => true,
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        FormTemplate::withoutGlobalScopes()->create([
            'organization_id' => $org2->id,
            'name' => 'Template Org 2',
            'is_active' => true,
            'public_enabled' => false,
        ]);

        $response = $this->actingAsTenant($user, $org1->id)
            ->withHeaders([
                'X-Organization-Id' => (string) $org2->id,
                'Accept' => 'application/json',
            ])->getJson('/api/v1/templates');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name')->all();
        $this->assertContains('Template Org 2', $names);
    }
}
