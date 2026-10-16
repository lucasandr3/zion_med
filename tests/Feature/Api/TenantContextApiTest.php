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

    public function test_login_opens_http_only_session_without_bearer(): void
    {
        $user = $this->qaClinicOwnerUser();
        $user->createToken('spa', SanctumTenantAbility::tokenAbilitiesForOrganization((int) $user->organization_id));

        $response = $this->withHeader('Origin', 'http://localhost:4200')
            ->postJson('/api/v1/auth/login', [
                'email' => $user->email,
                'password' => 'senha123',
            ]);

        $response->assertOk();
        $this->assertNull($response->json('data.token'));
        $this->assertNotEmpty($response->json('data.csrf_token'));
        $this->assertSame(0, $user->tokens()->where('name', 'spa')->count());

        $sessionCookie = $response->getCookie(config('session.cookie'));
        $this->assertNotNull($sessionCookie);
        $this->assertTrue($sessionCookie->isHttpOnly());

        $this->withCookie($sessionCookie->getName(), $sessionCookie->getValue())
            ->withHeader('Origin', 'http://localhost:4200')
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.organization.id', $user->organization_id);
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
