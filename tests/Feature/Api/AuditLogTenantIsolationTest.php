<?php

namespace Tests\Feature\Api;

use App\Models\AuditLog;
use App\Models\Clinic;
use App\Models\Tenant;
use App\Models\User;
use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuditLogTenantIsolationTest extends TestCase
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

    public function test_audit_logs_are_scoped_to_current_organization(): void
    {
        $tenant = Tenant::create(['name' => 'Tenant Audit', 'slug' => 'tenant-audit']);

        $orgA = Clinic::create([
            'tenant_id' => $tenant->id,
            'name' => 'Org Audit A',
            'slug' => 'org-audit-a',
            'notification_email' => 'a@test.com',
        ]);
        $orgB = Clinic::create([
            'tenant_id' => $tenant->id,
            'name' => 'Org Audit B',
            'slug' => 'org-audit-b',
            'notification_email' => 'b@test.com',
        ]);
        $this->activateOrganization($orgA);
        $this->activateOrganization($orgB);

        $user = User::withoutGlobalScopes()->create([
            'organization_id' => $orgA->id,
            'name' => 'Owner Audit',
            'email' => 'owner-audit@test.com',
            'password' => bcrypt('password'),
            'role' => Role::Owner->value,
            'active' => true,
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        AuditLog::withoutGlobalScopes()->create([
            'organization_id' => $orgA->id,
            'user_id' => $user->id,
            'action' => 'test.action.a',
            'created_at' => now(),
        ]);
        AuditLog::withoutGlobalScopes()->create([
            'organization_id' => $orgB->id,
            'user_id' => $user->id,
            'action' => 'test.action.b',
            'created_at' => now(),
        ]);

        Sanctum::actingAs($user);
        session(['current_clinic_id' => $orgA->id]);

        $response = $this->getJson('/api/v1/clinica/logs');

        $response->assertOk();
        $actions = collect($response->json('data'))->pluck('action')->all();
        $this->assertContains('test.action.a', $actions);
        $this->assertNotContains('test.action.b', $actions);
    }
}
