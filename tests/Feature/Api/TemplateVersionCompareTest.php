<?php

namespace Tests\Feature\Api;

use App\Models\FormTemplate;
use App\Models\FormTemplateVersion;
use App\Models\User;
use App\Services\TemplateVersionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TemplateVersionCompareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\OrganizationSeeder::class);
        $this->seed(\Database\Seeders\FormTemplateSeeder::class);
    }

    public function test_compare_returns_no_changes_when_single_version(): void
    {
        $user = $this->qaClinicOwnerUser();
        Sanctum::actingAs($user);
        session(['current_clinic_id' => $user->organization_id]);

        $template = FormTemplate::withoutGlobalScopes()
            ->where('organization_id', $user->organization_id)
            ->firstOrFail();

        /** @var TemplateVersionService $versions */
        $versions = app(TemplateVersionService::class);
        $versions->ensureSyncedVersion($template->fresh(['fields']));

        $this->getJson("/api/v1/templates/{$template->id}/versoes/comparar")
            ->assertOk()
            ->assertJsonPath('data.has_changes', false);
    }

    public function test_compare_detects_added_field_between_versions(): void
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

        $this->postJson("/api/v1/templates/{$template->id}/campos", [
            'type' => 'notice',
            'label' => 'Novo aviso clínico',
            'name_key' => 'aviso_novo_'.uniqid(),
            'required' => false,
        ])->assertCreated();

        $v2 = FormTemplateVersion::where('form_template_id', $template->id)->orderByDesc('version')->first();
        $this->assertNotNull($v2);
        $this->assertGreaterThan((int) $v1->version, (int) $v2->version);

        $this->getJson("/api/v1/templates/{$template->id}/versoes/comparar")
            ->assertOk()
            ->assertJsonPath('data.has_changes', true)
            ->assertJsonPath('data.from.version', (int) $v1->version)
            ->assertJsonPath('data.to.version', (int) $v2->version)
            ->assertJsonPath('data.fields.added.0.label', 'Novo aviso clínico');
    }

    public function test_list_versions_returns_ordered_metadata(): void
    {
        $user = $this->qaClinicOwnerUser();
        Sanctum::actingAs($user);
        session(['current_clinic_id' => $user->organization_id]);

        $template = FormTemplate::withoutGlobalScopes()
            ->where('organization_id', $user->organization_id)
            ->firstOrFail();

        /** @var TemplateVersionService $versions */
        $versions = app(TemplateVersionService::class);
        $versions->ensureSyncedVersion($template->fresh(['fields']));

        $this->postJson("/api/v1/templates/{$template->id}/campos", [
            'type' => 'heading',
            'label' => 'Seção extra',
            'name_key' => 'sec_extra_'.uniqid(),
            'required' => false,
        ])->assertCreated();

        $this->getJson("/api/v1/templates/{$template->id}/versoes")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.version', 2)
            ->assertJsonPath('data.1.version', 1);
    }
}
