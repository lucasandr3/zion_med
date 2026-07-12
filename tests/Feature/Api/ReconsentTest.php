<?php

namespace Tests\Feature\Api;

use App\Enums\SubmissionStatus;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReconsentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\OrganizationSeeder::class);
        Mail::fake();
    }

    private function actingOwner(): User
    {
        $user = $this->qaClinicOwnerUser();
        Sanctum::actingAs($user);
        session(['current_clinic_id' => $user->organization_id]);

        return $user;
    }

    private function createExpiredConsentProtocol(User $user): FormSubmission
    {
        $template = FormTemplate::withoutGlobalScopes()->create([
            'organization_id' => $user->organization_id,
            'name' => 'TCLE Vencido',
            'category' => 'consentimento',
            'document_kind' => 'consentimento',
            'consent_validity_days' => 30,
            'is_active' => true,
            'public_enabled' => true,
            'public_token' => str_repeat('r', 32),
        ]);

        $person = Person::withoutGlobalScopes()->create([
            'organization_id' => $user->organization_id,
            'code' => 'P-RECON-01',
            'name' => 'Paciente Reconsent',
            'email' => 'reconsent@test.com',
            'status' => 'active',
        ]);

        return FormSubmission::withoutGlobalScopes()->create([
            'organization_id' => $user->organization_id,
            'template_id' => $template->id,
            'person_id' => $person->id,
            'protocol_number' => 'P-RECON-001',
            'submitter_name' => 'Paciente Reconsent',
            'status' => SubmissionStatus::Approved,
            'submitted_at' => now()->subDays(60),
            'approved_at' => now()->subDays(59),
            'consent_valid_until' => now()->subDay(),
        ]);
    }

    public function test_protocol_reconsentimento_creates_document_send(): void
    {
        $user = $this->actingOwner();
        $protocol = $this->createExpiredConsentProtocol($user);

        $this->postJson("/api/v1/protocols/{$protocol->id}/reconsentimento", [
            'channel' => 'email',
        ])->assertCreated()
            ->assertJsonPath('data.channel', 'email');

        $this->assertDatabaseHas('document_sends', [
            'form_template_id' => $protocol->template_id,
            'person_id' => $protocol->person_id,
            'recipient_email' => 'reconsent@test.com',
        ]);

        $this->assertDatabaseHas('submission_events', [
            'form_submission_id' => $protocol->id,
            'type' => 'reconsent_requested',
        ]);
    }

    public function test_reconsentimento_rejects_valid_consent(): void
    {
        $user = $this->actingOwner();
        $protocol = $this->createExpiredConsentProtocol($user);
        $protocol->update(['consent_valid_until' => now()->addMonth()]);

        $this->postJson("/api/v1/protocols/{$protocol->id}/reconsentimento")
            ->assertStatus(422)
            ->assertJsonValidationErrors(['consent']);
    }
}
