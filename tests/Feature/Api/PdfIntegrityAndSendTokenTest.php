<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Exceptions\PdfIntegrityException;
use App\Models\DocumentSend;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Services\DocumentSendService;
use App\Services\PdfService;
use Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class PdfIntegrityAndSendTokenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(OrganizationSeeder::class);
        Storage::fake('minio_submissions');
    }

    public function test_pdf_integrity_mismatch_throws(): void
    {
        $owner = $this->qaClinicOwnerUser();
        $submission = FormSubmission::withoutGlobalScopes()->create([
            'organization_id' => $owner->organization_id,
            'template_id' => FormTemplate::withoutGlobalScopes()->create([
                'organization_id' => $owner->organization_id,
                'name' => 'PDF Integrity',
                'category' => 'ficha',
                'is_active' => true,
                'public_enabled' => true,
                'public_token' => str_repeat('p', 32),
            ])->id,
            'status' => 'pending',
            'submitted_at' => now(),
            'protocol_number' => 'GST-PDF-1',
            'pdf_disk_path' => 'org-1/protocols/1/protocolo-x.pdf',
            'pdf_sha256' => hash('sha256', 'conteudo-original'),
            'pdf_generated_at' => now(),
        ]);

        Storage::disk('minio_submissions')->put($submission->pdf_disk_path, 'conteudo-adulterado');

        $this->expectException(PdfIntegrityException::class);
        app(PdfService::class)->getSubmissionPdfContent($submission);
    }

    public function test_cancelled_send_token_returns_410_on_public_form(): void
    {
        $owner = $this->qaClinicOwnerUser();
        $template = FormTemplate::withoutGlobalScopes()->create([
            'organization_id' => $owner->organization_id,
            'name' => 'Send Token Form',
            'category' => 'ficha',
            'is_active' => true,
            'public_enabled' => true,
            'public_token' => str_repeat('s', 32),
        ]);

        $send = DocumentSend::create([
            'organization_id' => $owner->organization_id,
            'form_template_id' => $template->id,
            'recipient_email' => 'paciente@gestgo.test',
            'channel' => 'email',
            'sent_at' => now(),
            'public_token' => $template->public_token,
        ]);
        $this->assertNotEmpty($send->send_token);

        app(DocumentSendService::class)->cancel($send);

        $this->getJson('/api/v1/formulario-publico/'.$template->public_token.'?s='.$send->send_token)
            ->assertStatus(410);

        // Link compartilhado (sem ?s=) continua ok para Totem/QR.
        $this->getJson('/api/v1/formulario-publico/'.$template->public_token)
            ->assertOk();
    }
}
