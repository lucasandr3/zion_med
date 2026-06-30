<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiErrorEnvelopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_me_returns_standard_envelope(): void
    {
        $this->getJson('/api/v1/me')
            ->assertUnauthorized()
            ->assertJsonStructure(['code', 'message'])
            ->assertJsonPath('code', 'unauthorized');
    }

    public function test_validation_error_returns_code_details_and_legacy_errors(): void
    {
        $this->postJson('/api/v1/auth/login', [])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'validation_failed')
            ->assertJsonStructure([
                'code',
                'message',
                'details' => ['email', 'password'],
                'errors' => ['email', 'password'],
            ]);
    }

    public function test_tenant_forbidden_returns_standard_envelope(): void
    {
        $this->seed(\Database\Seeders\OrganizationSeeder::class);

        $admin = User::withoutGlobalScopes()->where('email', 'admin@gestgo.com.br')->firstOrFail();
        $admin->forceFill(['email_verified_at' => now()])->save();

        $this->actingAs($admin)
            ->getJson('/api/v1/dashboard')
            ->assertForbidden()
            ->assertJsonStructure(['code', 'message'])
            ->assertJsonPath('code', 'forbidden');
    }

    public function test_route_binding_not_found_returns_standard_envelope(): void
    {
        $this->seed(\Database\Seeders\OrganizationSeeder::class);

        $user = $this->qaClinicOwnerUser();
        $user->forceFill(['email_verified_at' => now()])->save();

        \Laravel\Sanctum\Sanctum::actingAs($user);
        session([
            'current_clinic_id' => $user->organization_id,
            'current_organization_id' => $user->organization_id,
        ]);

        $this->getJson('/api/v1/protocols/999999')
            ->assertNotFound()
            ->assertJsonPath('code', 'not_found')
            ->assertJsonStructure(['code', 'message']);
    }
}
