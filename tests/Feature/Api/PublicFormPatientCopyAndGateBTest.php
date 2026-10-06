<?php

namespace Tests\Feature\Api;

use App\Models\FormField;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\Organization;
use App\Models\Person;
use App\Models\SubmissionEvent;
use Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicFormPatientCopyAndGateBTest extends TestCase
{
    use RefreshDatabase;

    /** CPF válido para a Rule\Cpf. */
    private const CPF = '39053344705';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(OrganizationSeeder::class);
        Storage::fake('minio_submissions');
        Storage::fake('minio_attachments');
        Mail::fake();
    }

    public function test_gate_b_requires_name_confirmation_on_submit(): void
    {
        [$template, $person] = $this->makeCpfLinkedTemplate();

        $this->postJson("/api/v1/formulario-publico/{$template->public_token}/validate-person", [
            'cpf' => self::CPF,
            'birth_date' => '1990-05-10',
        ])->assertOk()
            ->assertJsonPath('data.name', $person->name);

        $this->postJson("/api/v1/formulario-publico/{$template->public_token}/submit", [
            '_submitter_name' => 'Teste',
            '_submitter_email' => 'paciente@example.com',
            '_person_cpf' => self::CPF,
            '_person_birth_date' => '1990-05-10',
            // sem _person_name_confirmed
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['_person_name_confirmed']);
    }

    public function test_submit_with_gate_b_returns_patient_copy_and_identity_snapshot(): void
    {
        [$template, $person] = $this->makeCpfLinkedTemplate();
        $org = Organization::query()->findOrFail($template->organization_id);
        $org->update(['notification_email' => 'clinic@example.com']);

        $response = $this->postJson("/api/v1/formulario-publico/{$template->public_token}/submit", [
            '_submitter_name' => 'Teste',
            '_submitter_email' => 'paciente@example.com',
            '_person_cpf' => self::CPF,
            '_person_birth_date' => '1990-05-10',
            '_person_name_confirmed' => true,
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'protocol_number',
                    'patient_download_token',
                    'patient_download_url',
                    'patient_download_expires_at',
                ],
            ]);

        $this->assertNotEmpty($response->json('data.protocol_number'));
        $token = $response->json('data.patient_download_token');
        $this->assertNotEmpty($token);

        $submission = FormSubmission::withoutGlobalScopes()
            ->where('protocol_number', $response->json('data.protocol_number'))
            ->firstOrFail();

        $this->assertSame($person->id, $submission->person_id);
        $this->assertNotEmpty($submission->pdf_disk_path);
        $this->assertNotEmpty($submission->pdf_sha256);
        $this->assertIsArray($submission->document_snapshot['identity'] ?? null);
        $this->assertSame($person->name, $submission->document_snapshot['identity']['full_name']);
        $this->assertTrue((bool) $submission->document_snapshot['identity']['name_confirmed']);
        $this->assertSame(self::CPF, $submission->document_snapshot['identity']['cpf']);

        $this->assertDatabaseHas('submission_events', [
            'form_submission_id' => $submission->id,
            'type' => 'patient_copy_token_issued',
        ]);
        $this->assertDatabaseHas('submission_events', [
            'form_submission_id' => $submission->id,
            'type' => 'patient_copy_emailed',
        ]);

        $download = $this->get("/api/v1/formulario-publico/copia/{$token}");
        $download->assertOk();
        $this->assertStringContainsString('pdf', strtolower((string) $download->headers->get('Content-Type')));

        $this->assertNotNull($submission->fresh()->patient_copy_downloaded_at);
        $this->assertTrue(
            SubmissionEvent::query()
                ->where('form_submission_id', $submission->id)
                ->where('type', 'patient_copy_downloaded')
                ->exists()
        );
    }

    public function test_patient_copy_email_endpoint_sends_to_provided_address(): void
    {
        [$template, $person] = $this->makeCpfLinkedTemplate();
        $person->update(['email' => null]);

        $submit = $this->postJson("/api/v1/formulario-publico/{$template->public_token}/submit", [
            '_submitter_name' => 'Teste',
            '_person_cpf' => self::CPF,
            '_person_birth_date' => '1990-05-10',
            '_person_name_confirmed' => true,
            // sem e-mail no submit
        ]);

        $submit->assertCreated();
        $token = $submit->json('data.patient_download_token');
        $this->assertNotEmpty($token);
        $this->assertFalse((bool) $submit->json('data.patient_copy_emailed'));

        $this->postJson("/api/v1/formulario-publico/copia/{$token}/email", [
            'email' => 'novo@example.com',
        ])->assertOk()
            ->assertJsonPath('data.message', fn ($m) => is_string($m) && $m !== '');

        $submission = FormSubmission::withoutGlobalScopes()
            ->where('protocol_number', $submit->json('data.protocol_number'))
            ->firstOrFail();

        $this->assertNotNull($submission->patient_copy_emailed_at);
        $this->assertSame('novo@example.com', strtolower((string) $submission->submitter_email));
        $this->assertDatabaseHas('submission_events', [
            'form_submission_id' => $submission->id,
            'type' => 'patient_copy_emailed',
        ]);
    }

    public function test_patient_copy_token_rejects_invalid(): void
    {
        $this->getJson('/api/v1/formulario-publico/copia/'.str_repeat('z', 32))
            ->assertNotFound();
    }

    /**
     * @return array{0: FormTemplate, 1: Person}
     */
    private function makeCpfLinkedTemplate(): array
    {
        $org = Organization::query()->firstOrFail();

        $person = Person::withoutGlobalScopes()->create([
            'organization_id' => $org->id,
            'name' => 'Maria Paciente',
            'code' => 'P001',
            'cpf' => self::CPF,
            'birth_date' => '1990-05-10',
            'email' => 'paciente@example.com',
            'status' => 'active',
        ]);

        $template = FormTemplate::withoutGlobalScopes()->create([
            'organization_id' => $org->id,
            'name' => 'Ficha Gate B',
            'category' => 'anamnese',
            'document_kind' => 'ficha',
            'is_active' => true,
            'public_enabled' => true,
            'public_require_person_link' => true,
            'public_person_link_mode' => 'cpf',
            'public_token' => str_repeat('g', 32),
        ]);

        FormField::create([
            'template_id' => $template->id,
            'type' => 'text',
            'label' => 'Observação',
            'name_key' => 'observacao',
            'required' => false,
            'sort_order' => 0,
        ]);

        return [$template, $person];
    }
}
