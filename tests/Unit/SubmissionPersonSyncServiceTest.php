<?php

namespace Tests\Unit;

use App\Models\FormField;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\User;
use App\Services\Submissions\SubmissionPersonSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubmissionPersonSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\OrganizationSeeder::class);
    }

    public function test_signature_field_with_paciente_suffix_is_not_used_as_person_name(): void
    {
        $user = $this->qaClinicOwnerUser();
        $template = FormTemplate::withoutGlobalScopes()->create([
            'organization_id' => $user->organization_id,
            'name' => 'TCLE',
            'document_kind' => 'consentimento',
            'is_active' => true,
            'public_enabled' => true,
            'created_by' => $user->id,
        ]);

        FormField::create([
            'template_id' => $template->id,
            'type' => 'text',
            'label' => 'Nome do paciente',
            'name_key' => 'nome_paciente',
            'required' => true,
            'sort_order' => 1,
        ]);
        FormField::create([
            'template_id' => $template->id,
            'type' => 'signature',
            'label' => 'Assinatura do paciente',
            'name_key' => 'assinatura_paciente',
            'required' => false,
            'sort_order' => 2,
        ]);

        $submission = new FormSubmission([
            'submitter_name' => 'Maria Silva',
            'submitter_email' => null,
        ]);

        $service = app(SubmissionPersonSyncService::class);
        $fields = $service->extractPersonFieldsFromSubmission($template->load('fields'), [
            'nome_paciente' => 'Maria Silva',
            'assinatura_paciente' => 'data:image/png;base64,'.str_repeat('A', 500),
        ], $submission);

        $this->assertSame('Maria Silva', $fields['name']);
    }
}
