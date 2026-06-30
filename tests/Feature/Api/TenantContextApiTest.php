<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Support\SanctumTenantAbility;
use Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TenantContextApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(OrganizationSeeder::class);
    }

    public function test_login_token_stores_tenant_ability(): void
    {
        $user = $this->qaClinicOwnerUser();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'senha123',
        ]);

        $response->assertOk();
        $plainToken = $response->json('data.token');
        $this->assertNotEmpty($plainToken);

        $tokenModel = $user->tokens()->where('name', 'spa')->first();
        $this->assertNotNull($tokenModel);
        $this->assertContains(
            SanctumTenantAbility::abilityFor((int) $user->organization_id),
            $tokenModel->abilities ?? []
        );
    }

    public function test_me_uses_tenant_from_token_without_session(): void
    {
        $user = $this->qaClinicOwnerUser();
        Sanctum::actingAs($user, SanctumTenantAbility::tokenAbilitiesForOrganization((int) $user->organization_id));

        $response = $this->getJson('/api/v1/me');

        $response->assertOk()
            ->assertJsonPath('data.organization.id', $user->organization_id);
    }
}
