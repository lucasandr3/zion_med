<?php

namespace Tests\Feature\Api;

use App\Models\FormField;
use App\Models\FormTemplate;
use Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicFormApiSubmitTest extends TestCase
{
    use RefreshDatabase;

    private const PNG_1PX_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    public function test_submit_accepts_file_field_as_data_url_json(): void
    {
        Storage::fake('minio_attachments');

        $this->seed(OrganizationSeeder::class);
        $clinic = \App\Models\Clinic::query()->firstOrFail();

        $template = FormTemplate::withoutGlobalScopes()->create([
            'organization_id' => $clinic->id,
            'name' => 'Form com foto',
            'description' => null,
            'category' => 'geral',
            'is_active' => true,
            'public_enabled' => true,
            'public_require_person_link' => false,
            'public_token' => str_repeat('c', 32),
        ]);

        FormField::create([
            'template_id' => $template->id,
            'type' => 'file',
            'label' => 'Foto',
            'name_key' => 'foto',
            'required' => false,
            'sort_order' => 0,
        ]);

        $token = $template->public_token;
        $payload = [
            '_submitter_name' => 'API Test',
            'foto' => 'data:image/png;base64,'.self::PNG_1PX_BASE64,
        ];

        $response = $this->postJson("/api/v1/formulario-publico/{$token}/submit", $payload);

        $response->assertCreated()
            ->assertJsonPath('data.message', 'Formulário enviado com sucesso.');

        $this->assertDatabaseHas('submission_attachments', [
            'field_key' => 'foto',
        ]);
    }

    public function test_submit_accepts_file_field_as_raw_base64_json(): void
    {
        Storage::fake('minio_attachments');

        $this->seed(OrganizationSeeder::class);
        $clinic = \App\Models\Clinic::query()->firstOrFail();

        $template = FormTemplate::withoutGlobalScopes()->create([
            'organization_id' => $clinic->id,
            'name' => 'Form com foto',
            'description' => null,
            'category' => 'geral',
            'is_active' => true,
            'public_enabled' => true,
            'public_require_person_link' => false,
            'public_token' => str_repeat('d', 32),
        ]);

        FormField::create([
            'template_id' => $template->id,
            'type' => 'file',
            'label' => 'Foto',
            'name_key' => 'foto',
            'required' => false,
            'sort_order' => 0,
        ]);

        $token = $template->public_token;
        $payload = [
            '_submitter_name' => 'API Test',
            'foto' => self::PNG_1PX_BASE64,
        ];

        $response = $this->postJson("/api/v1/formulario-publico/{$token}/submit", $payload);

        $response->assertCreated();
        $this->assertDatabaseHas('submission_attachments', ['field_key' => 'foto']);
    }
}
