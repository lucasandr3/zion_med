<?php

namespace Tests\Feature\Api;

use App\Enums\SubmissionStatus;
use App\Models\FormField;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\User;
use App\Services\SignatureFieldResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AssistedCosignTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\OrganizationSeeder::class);
        Storage::fake('minio_submissions');
    }

    private function actingOwner(): User
    {
        $user = $this->qaClinicOwnerUser();
        Sanctum::actingAs($user);
        session(['current_clinic_id' => $user->organization_id]);

        return $user;
    }

    private function tinyPngDataUrl(): string
    {
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            true,
        );

        return 'data:image/png;base64,'.base64_encode($png ?: '');
    }

    public function test_assisted_mode_requires_professional_cosign(): void
    {
        $user = $this->actingOwner();

        $template = FormTemplate::withoutGlobalScopes()->create([
            'organization_id' => $user->organization_id,
            'name' => 'TCLE Assistido',
            'category' => 'consentimento',
            'document_kind' => 'consentimento',
            'is_active' => true,
            'public_enabled' => true,
            'public_token' => str_repeat('a', 32),
        ]);

        $this->postJson("/api/v1/formulario-publico/{$template->public_token}/submit", [
            '_submitter_name' => 'Paciente Assistido',
            '_comprehension_ack' => true,
            '_assisted_mode' => true,
            '_professional_explained' => true,
            '_professional_name' => 'Dr. Teste',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['_signature']);
    }

    public function test_assisted_mode_persists_professional_cosign_signature(): void
    {
        $user = $this->actingOwner();

        $template = FormTemplate::withoutGlobalScopes()->create([
            'organization_id' => $user->organization_id,
            'name' => 'TCLE Co-sign',
            'category' => 'consentimento',
            'document_kind' => 'consentimento',
            'is_active' => true,
            'public_enabled' => true,
            'public_token' => str_repeat('b', 32),
        ]);

        FormField::withoutGlobalScopes()->create([
            'template_id' => $template->id,
            'type' => 'signature',
            'label' => 'Assinatura do paciente',
            'name_key' => 'assinatura_paciente',
            'required' => false,
            'sort_order' => 1,
        ]);

        $cosignKey = app(SignatureFieldResolver::class)->resolveProfessionalFieldKey($template->fresh(['fields']));

        $this->postJson("/api/v1/formulario-publico/{$template->public_token}/submit", [
            '_submitter_name' => 'Paciente Assistido',
            '_comprehension_ack' => true,
            '_accept_terms' => true,
            '_assisted_mode' => true,
            '_professional_explained' => true,
            '_professional_name' => 'Dra. Ana Profissional',
            '_signature' => [
                'assinatura_paciente' => $this->tinyPngDataUrl(),
                $cosignKey => $this->tinyPngDataUrl(),
            ],
        ])->assertCreated();

        $submission = FormSubmission::withoutGlobalScopes()
            ->where('template_id', $template->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($submission);
        $clinical = $submission->document_snapshot['clinical'] ?? [];
        $this->assertTrue((bool) ($clinical['professional_cosigned_at_submit'] ?? false));
        $this->assertSame('Dra. Ana Profissional', $clinical['professional_name_at_submit'] ?? null);

        $professionalSignature = $submission->signatures()->where('field_key', $cosignKey)->first();
        $this->assertNotNull($professionalSignature);
        $this->assertSame('assisted_cosign', $professionalSignature->channel);
        $this->assertSame('Dra. Ana Profissional', $professionalSignature->signed_name);
    }

    public function test_approval_does_not_duplicate_professional_signature_after_cosign(): void
    {
        $user = $this->actingOwner();
        Storage::disk('minio_submissions')->put(
            'users/'.$user->id.'/electronic-signature.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', true) ?: '',
        );
        $user->update(['electronic_signature_path' => 'users/'.$user->id.'/electronic-signature.png']);

        $template = FormTemplate::withoutGlobalScopes()->create([
            'organization_id' => $user->organization_id,
            'name' => 'TCLE Aprovação',
            'category' => 'consentimento',
            'document_kind' => 'consentimento',
            'is_active' => true,
            'public_enabled' => true,
            'public_token' => str_repeat('c', 32),
        ]);

        FormField::withoutGlobalScopes()->create([
            'template_id' => $template->id,
            'type' => 'signature',
            'label' => 'Assinatura do paciente',
            'name_key' => 'assinatura_paciente',
            'required' => false,
            'sort_order' => 1,
        ]);

        FormField::withoutGlobalScopes()->create([
            'template_id' => $template->id,
            'type' => 'signature',
            'label' => 'Profissional responsável',
            'name_key' => 'assinatura_profissional',
            'required' => false,
            'sort_order' => 2,
        ]);

        $submission = FormSubmission::withoutGlobalScopes()->create([
            'organization_id' => $user->organization_id,
            'template_id' => $template->id,
            'protocol_number' => 'P-COSIGN-001',
            'submitter_name' => 'Paciente',
            'status' => SubmissionStatus::Pending,
            'submitted_at' => now(),
            'document_snapshot' => [
                'clinical' => [
                    'assisted_mode' => true,
                    'professional_cosigned_at_submit' => true,
                ],
            ],
        ]);

        $submission->signatures()->create([
            'form_template_version_id' => null,
            'image_path' => 'organizations/'.$user->organization_id.'/signatures/'.$submission->id.'/prof.png',
            'field_key' => 'assinatura_profissional',
            'channel' => 'assisted_cosign',
            'status' => 'completed',
            'signed_name' => 'Dra. Ana Profissional',
            'signed_at' => now(),
        ]);

        Storage::disk('minio_submissions')->put(
            'organizations/'.$user->organization_id.'/signatures/'.$submission->id.'/prof.png',
            'fake',
        );

        $this->postJson("/api/v1/protocols/{$submission->id}/revisao", [
            'status' => 'approved',
        ])->assertOk();

        $this->assertSame(1, $submission->fresh()->signatures()->count());
    }
}
