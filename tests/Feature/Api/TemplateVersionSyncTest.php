<?php

namespace Tests\Feature\Api;

use App\Models\FormField;
use App\Models\FormTemplate;
use App\Models\FormTemplateVersion;
use App\Models\User;
use App\Services\TemplateVersionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TemplateVersionSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\OrganizationSeeder::class);
        $this->seed(\Database\Seeders\FormTemplateSeeder::class);
    }

    public function test_editing_fields_creates_new_template_version(): void
    {
        $user = $this->qaClinicOwnerUser();
        Sanctum::actingAs($user);
        session(['current_clinic_id' => $user->organization_id]);

        $template = FormTemplate::withoutGlobalScopes()
            ->where('organization_id', $user->organization_id)
            ->firstOrFail();

        /** @var TemplateVersionService $versions */
        $versions = app(TemplateVersionService::class);
        $v1 = $versions->ensureSyncedVersion($template->fresh(['fields']));
        $this->assertSame(1, (int) $v1->version);

        $this->postJson("/api/v1/templates/{$template->id}/campos", [
            'type' => 'notice',
            'label' => 'Riscos do procedimento: hematoma, edema e assimetria.',
            'name_key' => 'aviso_riscos_'.uniqid(),
            'required' => false,
        ])->assertCreated();

        $latest = FormTemplateVersion::where('form_template_id', $template->id)->orderByDesc('version')->first();
        $this->assertNotNull($latest);
        $this->assertGreaterThan(1, (int) $latest->version);
        $this->assertTrue(collect($latest->fields_snapshot)->contains(fn ($f) => ($f['type'] ?? '') === 'notice'));
    }

    public function test_document_kind_is_persisted(): void
    {
        $user = $this->qaClinicOwnerUser();
        Sanctum::actingAs($user);
        session(['current_clinic_id' => $user->organization_id]);

        $template = FormTemplate::withoutGlobalScopes()
            ->where('organization_id', $user->organization_id)
            ->firstOrFail();

        $this->putJson("/api/v1/templates/{$template->id}", [
            'name' => $template->name,
            'document_kind' => 'consentimento',
        ])->assertOk()
            ->assertJsonPath('data.document_kind', 'consentimento');

        $this->assertSame('consentimento', $template->fresh()->document_kind);
    }

    public function test_structural_heading_field_is_accepted(): void
    {
        $user = $this->qaClinicOwnerUser();
        Sanctum::actingAs($user);
        session(['current_clinic_id' => $user->organization_id]);

        $template = FormTemplate::withoutGlobalScopes()
            ->where('organization_id', $user->organization_id)
            ->firstOrFail();

        $this->postJson("/api/v1/templates/{$template->id}/campos", [
            'type' => 'heading',
            'label' => 'Riscos e cuidados',
            'name_key' => 'sec_riscos_'.uniqid(),
            'required' => true,
        ])->assertCreated()
            ->assertJsonPath('data.type', 'heading')
            ->assertJsonPath('data.required', false);
    }
}
