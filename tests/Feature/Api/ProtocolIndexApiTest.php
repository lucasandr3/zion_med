<?php

namespace Tests\Feature\Api;

use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProtocolIndexApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\OrganizationSeeder::class);
        $this->seed(\Database\Seeders\FormTemplateSeeder::class);
    }

    private function tenantUser(): User
    {
        return $this->qaClinicOwnerUser();
    }

    public function test_protocols_index_requires_auth(): void
    {
        $this->getJson('/api/v1/protocols')->assertStatus(401);
    }

    public function test_protocols_index_returns_paginated_list_for_tenant(): void
    {
        $user = $this->tenantUser();
        Sanctum::actingAs($user);
        session(['current_clinic_id' => $user->organization_id]);

        $template = FormTemplate::withoutGlobalScopes()
            ->where('organization_id', $user->organization_id)
            ->first();

        FormSubmission::withoutGlobalScopes()->create([
            'organization_id' => $user->organization_id,
            'template_id' => $template->id,
            'protocol_number' => 'P-INDEX-001',
            'submitter_name' => 'Paciente Teste',
            'submitter_email' => 'paciente@test.com',
            'status' => 'pending',
        ]);

        $response = $this->getJson('/api/v1/protocols?per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);

        $numbers = collect($response->json('data'))->pluck('protocol_number')->all();
        $this->assertContains('P-INDEX-001', $numbers);
    }

    public function test_protocols_index_returns_403_billing_blocked_when_trial_expired(): void
    {
        $user = $this->tenantUser();
        $user->clinic->update([
            'trial_ends_at' => now()->subDay(),
            'subscription_status' => 'active',
            'billing_status' => 'ok',
        ]);

        Sanctum::actingAs($user);
        session([
            'current_organization_id' => $user->organization_id,
            'current_clinic_id' => $user->organization_id,
        ]);

        $this->getJson('/api/v1/protocols')
            ->assertStatus(403)
            ->assertJson(['code' => 'billing_blocked']);
    }
}
