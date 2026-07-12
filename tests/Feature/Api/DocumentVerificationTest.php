<?php

namespace Tests\Feature\Api;

use App\Models\FormSubmission;
use App\Models\FormTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\OrganizationSeeder::class);
        $this->seed(\Database\Seeders\FormTemplateSeeder::class);
    }

    public function test_verify_by_full_document_hash(): void
    {
        $submission = $this->createSubmissionWithHash('abcdef0123456789abcdef0123456789abcdef0123456789abcdef0123456789');

        $response = $this->getJson('/api/v1/verificar/'.$submission->document_hash);

        $response->assertOk()
            ->assertJsonPath('valid', true)
            ->assertJsonPath('data.protocol_number', 'P-VERIFY-001')
            ->assertJsonPath('data.document_hash', $submission->document_hash)
            ->assertJsonMissingPath('data.submitter_email')
            ->assertJsonMissingPath('data.values');
    }

    public function test_verify_by_hash_prefix_code(): void
    {
        $hash = 'a1b2c3d4e5f60718293a4b5c6d7e8f901234567890abcdef1234567890abcdef';
        $this->createSubmissionWithHash($hash);

        $response = $this->getJson('/api/v1/verificar/'.strtoupper(substr($hash, 0, 8)));

        $response->assertOk()
            ->assertJsonPath('valid', true)
            ->assertJsonPath('data.verification_code', strtoupper(substr($hash, 0, 8)));
    }

    public function test_verify_by_protocol_number(): void
    {
        $this->createSubmissionWithHash('1111222233334444555566667777888899990000aaaabbbbccccddddeeeeffff');

        $response = $this->getJson('/api/v1/verificar/P-VERIFY-001');

        $response->assertOk()
            ->assertJsonPath('valid', true)
            ->assertJsonPath('data.protocol_number', 'P-VERIFY-001');
    }

    public function test_verify_unknown_code_returns_404(): void
    {
        $response = $this->getJson('/api/v1/verificar/DEADBEEF');

        $response->assertNotFound()
            ->assertJsonPath('valid', false);
    }

    public function test_verify_does_not_require_auth(): void
    {
        $hash = 'ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff';
        $this->createSubmissionWithHash($hash);

        $this->getJson('/api/v1/verificar/'.substr($hash, 0, 8))
            ->assertOk();
    }

    public function test_verify_exposes_clinical_evidence_without_pii(): void
    {
        $submission = $this->createSubmissionWithHash('abcdef0123456789abcdef0123456789abcdef0123456789abcdef0123456789');
        $submission->update([
            'document_snapshot' => [
                'document_kind' => 'consentimento',
                'template_version' => 3,
                'clinical' => [
                    'comprehension_ack' => true,
                    'comprehension_ack_at' => '2026-07-10T12:00:00+00:00',
                    'term_scrolled_at' => '2026-07-10T11:59:00+00:00',
                    'privacy_ack' => true,
                    'comprehension_quiz_passed' => true,
                    'actors' => [
                        'guardian_name' => 'Maria Secreta',
                        'witness_name' => 'João Testemunha',
                    ],
                ],
            ],
            'approved_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/verificar/'.$submission->document_hash);

        $response->assertOk()
            ->assertJsonPath('data.evidence.comprehension_ack', true)
            ->assertJsonPath('data.evidence.term_scrolled_at', '2026-07-10T11:59:00+00:00')
            ->assertJsonPath('data.evidence.has_guardian', true)
            ->assertJsonPath('data.evidence.has_witness', true)
            ->assertJsonMissingPath('data.evidence.actors')
            ->assertJsonMissingPath('data.evidence.guardian_name');
    }

    private function createSubmissionWithHash(string $hash): FormSubmission
    {
        $template = FormTemplate::withoutGlobalScopes()->firstOrFail();

        return FormSubmission::withoutGlobalScopes()->create([
            'organization_id' => $template->organization_id,
            'template_id' => $template->id,
            'protocol_number' => 'P-VERIFY-001',
            'submitter_name' => 'Paciente Teste',
            'submitter_email' => 'secreto@example.com',
            'status' => 'pending',
            'document_hash' => $hash,
            'submitted_at' => now(),
        ]);
    }
}
