<?php

namespace Tests\Feature\Api;

use App\Models\FormField;
use App\Models\FormTemplate;
use App\Models\User;
use App\Services\TemplateLibraryCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpgradeTcleTemplatesCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\OrganizationSeeder::class);
    }

    public function test_upgrade_tcle_merge_adds_cfm_fields_to_legacy_template(): void
    {
        $user = $this->qaClinicOwnerUser();
        $template = FormTemplate::withoutGlobalScopes()->create([
            'organization_id' => $user->organization_id,
            'name' => 'TCLE — Termo de Consentimento Livre e Esclarecido',
            'description' => 'legado',
            'category' => 'cadastro_documentacao',
            'document_kind' => 'consentimento',
            'library_key' => 'estetica__tcle_termo_de_consentimento_livre_e_esclarecido',
            'library_content_version' => 1,
            'is_active' => true,
            'public_enabled' => false,
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
            'type' => 'checkbox',
            'label' => 'Autorizo o procedimento',
            'name_key' => 'decl_autorizo_proc',
            'required' => true,
            'sort_order' => 2,
        ]);
        FormField::create([
            'template_id' => $template->id,
            'type' => 'signature',
            'label' => 'Assinatura',
            'name_key' => 'assinatura_paciente',
            'required' => true,
            'sort_order' => 3,
        ]);

        $this->artisan('templates:upgrade-tcle', [
            '--organization' => $user->organization_id,
            '--mode' => 'merge',
        ])->assertSuccessful();

        $template->refresh()->load('fields');
        $this->assertSame(2, (int) $template->library_content_version);
        $this->assertSame('consentimento', $template->category);
        $this->assertTrue($template->fields->contains(fn ($f) => $f->name_key === 'riscos_intercorrencias'));
        $this->assertTrue($template->fields->contains(fn ($f) => $f->name_key === 'alternativas_tratamento'));
        $this->assertTrue($template->fields->contains(fn ($f) => $f->name_key === 'descricao_procedimento'));

        $issues = app(\App\Services\ClinicalStepValidationService::class)->validateForPublish($template);
        $blocking = array_filter(
            $issues,
            fn ($i) => in_array($i['code'], app(\App\Services\ClinicalStepValidationService::class)->blockingCodes(), true)
        );
        $this->assertSame([], array_values($blocking));
    }

    public function test_upgrade_tcle_dry_run_does_not_persist(): void
    {
        $user = $this->qaClinicOwnerUser();
        $template = FormTemplate::withoutGlobalScopes()->create([
            'organization_id' => $user->organization_id,
            'name' => 'TCLE — Termo de Consentimento Livre e Esclarecido',
            'category' => 'consentimento',
            'document_kind' => 'consentimento',
            'library_key' => 'estetica__tcle_termo_de_consentimento_livre_e_esclarecido',
            'library_content_version' => 1,
            'is_active' => true,
            'public_enabled' => false,
            'created_by' => $user->id,
        ]);
        FormField::create([
            'template_id' => $template->id,
            'type' => 'text',
            'label' => 'Nome',
            'name_key' => 'nome_paciente',
            'required' => true,
            'sort_order' => 1,
        ]);

        $before = $template->fields()->count();

        $this->artisan('templates:upgrade-tcle', [
            '--organization' => $user->organization_id,
            '--mode' => 'merge',
            '--dry-run' => true,
        ])->assertSuccessful();

        $this->assertSame($before, $template->fields()->count());
        $this->assertSame(1, (int) $template->fresh()->library_content_version);
    }

    public function test_catalog_resolves_estetica_tcle_definition(): void
    {
        $def = app(TemplateLibraryCatalog::class)->findByKey(
            'estetica__tcle_termo_de_consentimento_livre_e_esclarecido'
        );
        $this->assertNotNull($def);
        $this->assertSame('consentimento', $def['document_kind']);
        $this->assertGreaterThan(10, count($def['fields']));
    }
}
