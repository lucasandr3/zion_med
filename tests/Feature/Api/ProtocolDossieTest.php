<?php

namespace Tests\Feature\Api;

use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProtocolDossieTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\ClinicSeeder::class);
        $this->seed(\Database\Seeders\FormTemplateSeeder::class);
    }

    private function tenantUser(): User
    {
        return User::withoutGlobalScopes()->where('email', 'admin@demo.zionmed.com')->first();
    }

    public function test_dossie_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/protocols/999/dossie');
        $response->assertStatus(401);
    }

    public function test_authorized_user_receives_dossie_zip(): void
    {
        $user = $this->tenantUser();
        Sanctum::actingAs($user);
        session(['current_clinic_id' => $user->organization_id]);

        $template = FormTemplate::withoutGlobalScopes()->where('organization_id', $user->organization_id)->first();
        $submission = FormSubmission::withoutGlobalScopes()->create([
            'organization_id' => $user->organization_id,
            'template_id' => $template->id,
            'protocol_number' => 'P-DOSSIE-001',
            'submitter_name' => 'Test',
            'submitter_email' => 'test@example.com',
            'status' => 'submitted',
        ]);

        $response = $this->get("/api/v1/protocols/{$submission->id}/dossie");
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/zip');
        $this->assertStringContainsString('dossie-', $response->headers->get('Content-Disposition') ?? '');
    }
}
