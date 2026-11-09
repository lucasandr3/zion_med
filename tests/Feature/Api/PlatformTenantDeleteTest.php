<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\Role;
use App\Models\Organization;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PlatformTenantDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(OrganizationSeeder::class);
    }

    public function test_platform_admin_can_delete_tenant_and_related_data(): void
    {
        $admin = User::withoutGlobalScopes()
            ->where('role', Role::PlatformAdmin->value)
            ->firstOrFail();

        Sanctum::actingAs($admin);

        $tenant = Tenant::create([
            'name' => 'Cliente Teste Exclusao',
            'slug' => 'cliente-teste-exclusao',
        ]);

        $organization = Organization::create([
            'tenant_id' => $tenant->id,
            'name' => 'Empresa Teste Exclusao',
            'slug' => 'empresa-teste-exclusao',
            'niche' => 'estetica',
            'notification_email' => 'teste-exclusao@gestgo.test',
            'subscription_status' => 'trial',
            'billing_status' => 'ok',
            'trial_ends_at' => now()->addDays(14),
        ]);

        $owner = User::create([
            'organization_id' => $organization->id,
            'name' => 'Owner Teste',
            'email' => 'owner-teste-exclusao@gestgo.test',
            'password' => 'SenhaForte1!',
            'role' => Role::Owner->value,
            'active' => true,
        ]);

        if (Schema::hasTable('product_events')) {
            DB::table('product_events')->insert([
                'event_name' => 'signup_completed',
                'idempotency_key' => 'signup_completed:org:'.$organization->id,
                'organization_id' => $organization->id,
                'user_id' => $owner->id,
                'channel' => 'comece',
                'occurred_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $response = $this->deleteJson(route('api.v1.platform.tenants.destroy', $tenant), [
            'confirm_name' => 'Cliente Teste Exclusao',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.message', 'Cliente e dados vinculados removidos.');

        $this->assertDatabaseMissing('tenants', ['id' => $tenant->id]);
        $this->assertDatabaseMissing('organizations', ['id' => $organization->id]);
        $this->assertDatabaseMissing('users', ['id' => $owner->id]);

        if (Schema::hasTable('product_events')) {
            $this->assertDatabaseMissing('product_events', [
                'organization_id' => $organization->id,
            ]);
        }
    }

    public function test_delete_requires_matching_confirm_name(): void
    {
        $admin = User::withoutGlobalScopes()
            ->where('role', Role::PlatformAdmin->value)
            ->firstOrFail();

        Sanctum::actingAs($admin);

        $tenant = Tenant::create([
            'name' => 'Nao Apagar',
            'slug' => 'nao-apagar',
        ]);

        $this->deleteJson(route('api.v1.platform.tenants.destroy', $tenant), [
            'confirm_name' => 'nome errado',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['confirm_name']);

        $this->assertDatabaseHas('tenants', ['id' => $tenant->id]);
    }

    public function test_non_platform_admin_cannot_delete_tenant(): void
    {
        $owner = $this->qaClinicOwnerUser();
        Sanctum::actingAs($owner);

        $tenant = Tenant::create([
            'name' => 'Bloqueado',
            'slug' => 'bloqueado-delete',
        ]);

        $this->deleteJson(route('api.v1.platform.tenants.destroy', $tenant), [
            'confirm_name' => 'Bloqueado',
        ])->assertForbidden();

        $this->assertDatabaseHas('tenants', ['id' => $tenant->id]);
    }
}
