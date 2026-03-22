<?php

namespace Tests\Feature\Api;

use App\Models\DocumentSend;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DocumentSendControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\ClinicSeeder::class);
    }

    private function tenantUser(): User
    {
        return $this->qaClinicOwnerUser();
    }

    public function test_document_sends_index_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/document-sends');
        $response->assertStatus(401);
    }

    public function test_document_sends_index_with_caixa_pendentes(): void
    {
        $user = $this->tenantUser();
        Sanctum::actingAs($user);
        session(['current_clinic_id' => $user->organization_id]);

        $template = FormTemplate::withoutGlobalScopes()->where('organization_id', $user->organization_id)->first();
        if (! $template) {
            $this->seed(\Database\Seeders\FormTemplateSeeder::class);
            $template = FormTemplate::withoutGlobalScopes()->where('organization_id', $user->organization_id)->first();
        }
        $this->assertNotNull($template);

        DocumentSend::create([
            'organization_id' => $user->organization_id,
            'form_template_id' => $template->id,
            'recipient_email' => 'dest@test.com',
            'channel' => 'email',
            'sent_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->getJson('/api/v1/document-sends?caixa=pendentes');
        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'meta', 'links']);
        $this->assertGreaterThanOrEqual(1, count($response->json('data')));
    }

    public function test_document_sends_index_with_caixa_cancelados(): void
    {
        $user = $this->tenantUser();
        Sanctum::actingAs($user);
        session(['current_clinic_id' => $user->organization_id]);

        $template = FormTemplate::withoutGlobalScopes()->where('organization_id', $user->organization_id)->first();
        if (! $template) {
            $this->seed(\Database\Seeders\FormTemplateSeeder::class);
            $template = FormTemplate::withoutGlobalScopes()->where('organization_id', $user->organization_id)->first();
        }

        DocumentSend::create([
            'organization_id' => $user->organization_id,
            'form_template_id' => $template->id,
            'recipient_email' => 'outro@test.com',
            'channel' => 'email',
            'sent_at' => now(),
            'cancelled_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/document-sends?caixa=cancelados');
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(1, count($data));
        $cancelado = collect($data)->firstWhere('status', 'cancelado');
        $this->assertNotNull($cancelado);
    }

    public function test_cancel_pending_send_returns_200(): void
    {
        $user = $this->tenantUser();
        Sanctum::actingAs($user);
        session(['current_clinic_id' => $user->organization_id]);

        $template = FormTemplate::withoutGlobalScopes()->where('organization_id', $user->organization_id)->first();
        if (! $template) {
            $this->seed(\Database\Seeders\FormTemplateSeeder::class);
            $template = FormTemplate::withoutGlobalScopes()->where('organization_id', $user->organization_id)->first();
        }

        $send = DocumentSend::create([
            'organization_id' => $user->organization_id,
            'form_template_id' => $template->id,
            'recipient_email' => 'cancel@test.com',
            'channel' => 'email',
            'sent_at' => now(),
        ]);

        $response = $this->postJson("/api/v1/document-sends/{$send->id}/cancel");
        $response->assertStatus(200);
        $response->assertJsonPath('data.message', 'Envio cancelado.');
        $send->refresh();
        $this->assertNotNull($send->cancelled_at);
    }

    public function test_cancel_already_signed_send_returns_422(): void
    {
        $user = $this->tenantUser();
        Sanctum::actingAs($user);
        session(['current_clinic_id' => $user->organization_id]);

        $template = FormTemplate::withoutGlobalScopes()->where('organization_id', $user->organization_id)->first();
        if (! $template) {
            $this->seed(\Database\Seeders\FormTemplateSeeder::class);
            $template = FormTemplate::withoutGlobalScopes()->where('organization_id', $user->organization_id)->first();
        }

        $submission = FormSubmission::withoutGlobalScopes()->create([
            'organization_id' => $user->organization_id,
            'template_id' => $template->id,
            'protocol_number' => 'P-001',
            'submitter_name' => 'Test',
            'submitter_email' => 'signed@test.com',
            'status' => 'pending',
        ]);

        $send = DocumentSend::create([
            'organization_id' => $user->organization_id,
            'form_template_id' => $template->id,
            'recipient_email' => 'signed@test.com',
            'channel' => 'email',
            'sent_at' => now(),
            'form_submission_id' => $submission->id,
        ]);

        $response = $this->postJson("/api/v1/document-sends/{$send->id}/cancel");
        $response->assertStatus(422);
        $send->refresh();
        $this->assertNull($send->cancelled_at);
    }
}
