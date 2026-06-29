<?php

namespace Tests\Feature\Api;

use App\Models\FormTemplate;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OnboardingApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\OrganizationSeeder::class);
        $this->seed(\Database\Seeders\FormTemplateSeeder::class);
    }

    private function actingOwner(): User
    {
        $user = $this->qaClinicOwnerUser();
        Sanctum::actingAs($user);
        session(['current_clinic_id' => $user->organization_id]);

        return $user;
    }

    public function test_onboarding_templates_lists_active_templates(): void
    {
        $user = $this->actingOwner();

        $response = $this->getJson('/api/v1/onboarding/templates');

        $response->assertOk();
        $this->assertNotEmpty($response->json('data'));
        $this->assertTrue(
            collect($response->json('data'))->every(fn (array $row) => ($row['is_active'] ?? true) !== false)
        );
    }

    public function test_onboarding_gerar_link_publico(): void
    {
        $user = $this->actingOwner();

        $template = FormTemplate::withoutGlobalScopes()
            ->where('organization_id', $user->organization_id)
            ->where('is_active', true)
            ->first();
        $this->assertNotNull($template);

        $response = $this->postJson("/api/v1/onboarding/templates/{$template->id}/link-publico");

        $response->assertOk();
        $this->assertNotEmpty($response->json('data.public_url'));

        $template->refresh();
        $this->assertNotNull($template->public_token);
    }
}
