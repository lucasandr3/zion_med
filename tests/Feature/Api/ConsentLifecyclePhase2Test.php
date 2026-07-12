<?php

namespace Tests\Feature\Api;

use App\Enums\SubmissionStatus;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ConsentLifecyclePhase2Test extends TestCase
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

    public function test_consentimento_submit_requires_comprehension_ack(): void
    {
        $user = $this->actingOwner();

        $template = FormTemplate::withoutGlobalScopes()->create([
            'organization_id' => $user->organization_id,
            'name' => 'TCLE Teste',
            'description' => null,
            'category' => 'consentimento',
            'document_kind' => 'consentimento',
            'is_active' => true,
            'public_enabled' => true,
            'public_require_person_link' => false,
            'public_token' => str_repeat('e', 32),
        ]);

        $token = $template->public_token;

        $this->postJson("/api/v1/formulario-publico/{$token}/submit", [
            '_submitter_name' => 'Paciente Sem Ack',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['_comprehension_ack']);

        $this->postJson("/api/v1/formulario-publico/{$token}/submit", [
            '_submitter_name' => 'Paciente Com Ack',
            '_comprehension_ack' => true,
            '_comprehension_ack_at' => now()->toIso8601String(),
            '_actors' => [
                'guardian_name' => 'Maria Responsável',
                'guardian_relation' => 'mãe',
                'witness_name' => 'João Testemunha',
            ],
        ])->assertCreated();

        $submission = FormSubmission::withoutGlobalScopes()
            ->where('template_id', $template->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($submission);
        $clinical = $submission->document_snapshot['clinical'] ?? [];
        $this->assertTrue((bool) ($clinical['comprehension_ack'] ?? false));
        $this->assertSame('Maria Responsável', $clinical['actors']['guardian_name'] ?? null);
    }

    public function test_approve_with_professional_explained_and_revoke(): void
    {
        $user = $this->actingOwner();

        $template = FormTemplate::withoutGlobalScopes()
            ->where('organization_id', $user->organization_id)
            ->firstOrFail();

        $template->update([
            'document_kind' => 'consentimento',
            'category' => 'consentimento',
        ]);

        $submission = FormSubmission::withoutGlobalScopes()->create([
            'organization_id' => $user->organization_id,
            'template_id' => $template->id,
            'protocol_number' => 'P-REV-001',
            'submitter_name' => 'Paciente',
            'submitter_email' => 'pac@test.com',
            'status' => SubmissionStatus::Pending,
            'submitted_at' => now(),
        ]);

        $this->postJson("/api/v1/protocols/{$submission->id}/revisao", [
            'status' => 'approved',
            'professional_explained' => true,
            'review_comment' => 'TCLE revisado',
        ])->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->postJson("/api/v1/protocols/{$submission->id}/revogar", [
            'reason' => 'Paciente desistiu do procedimento',
        ])->assertOk()
            ->assertJsonPath('data.status', 'revoked')
            ->assertJsonPath('data.revoke_reason', 'Paciente desistiu do procedimento');

        $this->assertSame(SubmissionStatus::Revoked, $submission->fresh()->status);
        $this->assertNotNull($submission->fresh()->revoked_at);
    }

    public function test_person_show_includes_consent_summary(): void
    {
        $user = $this->actingOwner();

        $template = FormTemplate::withoutGlobalScopes()
            ->where('organization_id', $user->organization_id)
            ->firstOrFail();
        $template->update([
            'document_kind' => 'consentimento',
            'category' => 'consentimento',
        ]);

        $person = Person::withoutGlobalScopes()->create([
            'organization_id' => $user->organization_id,
            'code' => 'P-CONSENT-01',
            'name' => 'Paciente Consentimento',
            'status' => 'active',
        ]);

        FormSubmission::withoutGlobalScopes()->create([
            'organization_id' => $user->organization_id,
            'template_id' => $template->id,
            'person_id' => $person->id,
            'protocol_number' => 'P-CONSENT-OK',
            'submitter_name' => 'Paciente Consentimento',
            'status' => SubmissionStatus::Approved,
            'submitted_at' => now(),
            'approved_at' => now(),
        ]);

        $this->getJson("/api/v1/pessoas/{$person->id}")
            ->assertOk()
            ->assertJsonPath('data.consent_summary.status', 'valid')
            ->assertJsonPath('data.consent_summary.active_protocol_number', 'P-CONSENT-OK')
            ->assertJsonPath('data.stats.revoked_protocols', 0);
    }

    public function test_approve_sets_consent_valid_until_and_person_summary_can_expire(): void
    {
        $user = $this->actingOwner();

        $template = FormTemplate::withoutGlobalScopes()
            ->where('organization_id', $user->organization_id)
            ->firstOrFail();
        $template->update([
            'document_kind' => 'consentimento',
            'category' => 'consentimento',
            'consent_validity_days' => 30,
        ]);

        $person = Person::withoutGlobalScopes()->create([
            'organization_id' => $user->organization_id,
            'code' => 'P-VALID-01',
            'name' => 'Paciente Validade',
            'status' => 'active',
        ]);

        $submission = FormSubmission::withoutGlobalScopes()->create([
            'organization_id' => $user->organization_id,
            'template_id' => $template->id,
            'person_id' => $person->id,
            'protocol_number' => 'P-VALID-001',
            'submitter_name' => 'Paciente Validade',
            'status' => SubmissionStatus::Pending,
            'submitted_at' => now(),
        ]);

        $this->postJson("/api/v1/protocols/{$submission->id}/revisao", [
            'status' => 'approved',
        ])->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $submission->refresh();
        $this->assertNotNull($submission->consent_valid_until);
        $this->assertTrue($submission->consent_valid_until->isFuture());
        $this->assertFalse($submission->isConsentExpired());

        $this->getJson("/api/v1/pessoas/{$person->id}")
            ->assertOk()
            ->assertJsonPath('data.consent_summary.status', 'valid');

        $submission->update(['consent_valid_until' => now()->subDay()]);

        $this->getJson("/api/v1/pessoas/{$person->id}")
            ->assertOk()
            ->assertJsonPath('data.consent_summary.status', 'expired')
            ->assertJsonPath('data.consent_summary.label', 'Consentimento vencido');
    }

    public function test_template_persists_consent_validity_days(): void
    {
        $user = $this->actingOwner();

        $template = FormTemplate::withoutGlobalScopes()
            ->where('organization_id', $user->organization_id)
            ->firstOrFail();

        $this->putJson("/api/v1/templates/{$template->id}", [
            'name' => $template->name,
            'document_kind' => 'consentimento',
            'consent_validity_days' => 180,
        ])->assertOk()
            ->assertJsonPath('data.consent_validity_days', 180);

        $this->assertSame(180, (int) $template->fresh()->consent_validity_days);
    }

    public function test_comprehension_quiz_required_and_hides_answer_key_publicly(): void
    {
        $user = $this->actingOwner();

        $template = FormTemplate::withoutGlobalScopes()->create([
            'organization_id' => $user->organization_id,
            'name' => 'TCLE Quiz',
            'category' => 'consentimento',
            'document_kind' => 'consentimento',
            'is_active' => true,
            'public_enabled' => true,
            'public_require_person_link' => false,
            'public_token' => str_repeat('q', 32),
            'comprehension_quiz' => [
                [
                    'id' => 'recusa',
                    'prompt' => 'Posso recusar o procedimento?',
                    'options' => ['Sim', 'Não'],
                    'correct_index' => 0,
                ],
            ],
        ]);

        $show = $this->getJson('/api/v1/formulario-publico/'.$template->public_token);
        $show->assertOk()
            ->assertJsonPath('data.comprehension_quiz.0.id', 'recusa')
            ->assertJsonPath('data.comprehension_quiz.0.prompt', 'Posso recusar o procedimento?')
            ->assertJsonMissingPath('data.comprehension_quiz.0.correct_index');

        $this->postJson("/api/v1/formulario-publico/{$template->public_token}/submit", [
            '_submitter_name' => 'Paciente Quiz',
            '_comprehension_ack' => true,
            '_comprehension_quiz' => ['recusa' => 1],
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['_comprehension_quiz']);

        $this->postJson("/api/v1/formulario-publico/{$template->public_token}/submit", [
            '_submitter_name' => 'Paciente Quiz',
            '_comprehension_ack' => true,
            '_comprehension_quiz' => ['recusa' => 0],
        ])->assertCreated();

        $submission = \App\Models\FormSubmission::withoutGlobalScopes()
            ->where('template_id', $template->id)
            ->latest('id')
            ->first();
        $this->assertTrue((bool) ($submission->document_snapshot['clinical']['comprehension_quiz_passed'] ?? false));
    }
}
