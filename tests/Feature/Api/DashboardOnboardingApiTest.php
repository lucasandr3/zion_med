<?php

namespace Tests\Feature\Api;

use App\Models\FormTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardOnboardingApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\OrganizationSeeder::class);
        $this->seed(\Database\Seeders\FormTemplateSeeder::class);
    }

    public function test_dashboard_includes_onboarding_when_no_public_links(): void
    {
        $user = $this->qaClinicOwnerUser();
        Sanctum::actingAs($user);
        session(['current_clinic_id' => $user->organization_id]);

        FormTemplate::withoutGlobalScopes()
            ->where('organization_id', $user->organization_id)
            ->update(['public_token' => null, 'public_enabled' => false]);

        Cache::forget('org:'.$user->organization_id.':dashboard:public_links_count');

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertOk();
        $response->assertJsonPath('data.onboarding.needs_public_link', true);
        $response->assertJsonPath('data.onboarding.public_links_count', 0);
    }

    public function test_dashboard_onboarding_complete_when_public_link_exists(): void
    {
        $user = $this->qaClinicOwnerUser();
        Sanctum::actingAs($user);
        session(['current_clinic_id' => $user->organization_id]);

        FormTemplate::withoutGlobalScopes()
            ->where('organization_id', $user->organization_id)
            ->limit(1)
            ->update([
                'public_token' => 'test-public-token-'.uniqid(),
                'public_enabled' => true,
            ]);

        Cache::forget('org:'.$user->organization_id.':dashboard:public_links_count');

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertOk();
        $response->assertJsonPath('data.onboarding.needs_public_link', false);
        $this->assertGreaterThanOrEqual(1, (int) $response->json('data.onboarding.public_links_count'));
    }
}
