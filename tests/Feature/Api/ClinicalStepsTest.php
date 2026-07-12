<?php

namespace Tests\Feature\Api;

use App\Models\FormField;
use App\Models\FormTemplate;
use App\Models\User;
use App\Support\ClinicalStepKind;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClinicalStepsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\OrganizationSeeder::class);
    }

    private function createConsentTemplate(User $user, array $overrides = []): FormTemplate
    {
        return FormTemplate::withoutGlobalScopes()->create(array_merge([
            'organization_id' => $user->organization_id,
            'name' => 'TCLE Teste',
            'description' => null,
            'category' => 'consentimento',
            'document_kind' => 'consentimento',
            'is_active' => true,
            'public_enabled' => false,
            'public_require_person_link' => false,
            'created_by' => $user->id,
        ], $overrides));
    }

    public function test_apply_tcle_structure_sets_clinical_step_kinds(): void
    {
        $user = $this->qaClinicOwnerUser();
        Sanctum::actingAs($user);
        $template = $this->createConsentTemplate($user);

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

        $response = $this->postJson("/api/v1/templates/{$template->id}/etapas-clinicas/aplicar-tcle");

        $response->assertOk();
        $template->refresh();
        $this->assertTrue($template->uses_clinical_steps);

        $kinds = $template->fields()->pluck('clinical_step_kind')->filter()->unique()->values()->all();
        $this->assertContains(ClinicalStepKind::DADOS_PACIENTE, $kinds);
        $this->assertContains(ClinicalStepKind::ASSINATURAS, $kinds);
        $this->assertContains(ClinicalStepKind::DESCRICAO_PROCEDIMENTO, $kinds);
        $this->assertContains(ClinicalStepKind::RISCOS_BENEFICIOS, $kinds);
        $this->assertContains(ClinicalStepKind::ALTERNATIVAS, $kinds);
    }

    public function test_publish_blocks_incomplete_consent_without_disclosure(): void
    {
        $user = $this->qaClinicOwnerUser();
        Sanctum::actingAs($user);
        $template = $this->createConsentTemplate($user);

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
            'name_key' => 'autorizacao',
            'required' => true,
            'sort_order' => 2,
        ]);
        FormField::create([
            'template_id' => $template->id,
            'type' => 'signature',
            'label' => 'Assinatura',
            'name_key' => 'assinatura',
            'required' => true,
            'sort_order' => 3,
        ]);

        $response = $this->postJson("/api/v1/templates/{$template->id}/link-publico");

        $response->assertStatus(422)
            ->assertJsonStructure(['clinical_validation']);
    }

    public function test_publish_allows_cfm_aligned_consent(): void
    {
        $user = $this->qaClinicOwnerUser();
        Sanctum::actingAs($user);
        $template = $this->createConsentTemplate($user, ['uses_clinical_steps' => true]);

        $order = 0;
        foreach ([
            ['notice', 'Descrição clara do procedimento terapêutico indicado ao paciente neste atendimento clínico.', 'n_proc', ClinicalStepKind::DESCRICAO_PROCEDIMENTO],
            ['notice', 'Riscos e intercorrências possíveis deste procedimento, incluindo efeitos colaterais relevantes.', 'n_risco', ClinicalStepKind::RISCOS_BENEFICIOS],
            ['notice', 'Alternativas terapêuticas disponíveis e consequências da não realização do procedimento proposto.', 'n_alt', ClinicalStepKind::ALTERNATIVAS],
            ['checkbox', 'Autorizo a realização do procedimento', 'autorizo', ClinicalStepKind::DECLARACOES],
            ['signature', 'Assinatura do paciente', 'assinatura', ClinicalStepKind::ASSINATURAS],
        ] as [$type, $label, $key, $kind]) {
            FormField::create([
                'template_id' => $template->id,
                'type' => $type,
                'label' => $label,
                'name_key' => $key,
                'required' => $type !== 'notice',
                'clinical_step_kind' => $kind,
                'sort_order' => ++$order,
            ]);
        }

        $response = $this->postJson("/api/v1/templates/{$template->id}/link-publico");

        $response->assertOk();
    }

    public function test_public_form_exposes_clinical_step_kind(): void
    {
        $user = $this->qaClinicOwnerUser();
        $template = $this->createConsentTemplate($user, [
            'uses_clinical_steps' => true,
            'public_enabled' => true,
            'public_token' => str_repeat('c', 32),
        ]);

        FormField::create([
            'template_id' => $template->id,
            'type' => 'notice',
            'label' => 'Riscos do procedimento',
            'name_key' => 'notice_riscos',
            'required' => false,
            'clinical_step_kind' => ClinicalStepKind::RISCOS_BENEFICIOS,
            'sort_order' => 1,
        ]);

        $response = $this->getJson('/api/v1/formulario-publico/'.$template->public_token);

        $response->assertOk()
            ->assertJsonPath('data.uses_clinical_steps', true)
            ->assertJsonPath('data.fields.0.clinical_step_kind', ClinicalStepKind::RISCOS_BENEFICIOS);
    }

    public function test_publish_blocks_when_clinical_steps_enabled_without_kinds(): void
    {
        $user = $this->qaClinicOwnerUser();
        Sanctum::actingAs($user);
        $template = $this->createConsentTemplate($user, ['uses_clinical_steps' => true]);

        FormField::create([
            'template_id' => $template->id,
            'type' => 'text',
            'label' => 'Campo',
            'name_key' => 'campo',
            'required' => false,
            'sort_order' => 1,
        ]);

        $response = $this->postJson("/api/v1/templates/{$template->id}/link-publico");

        $response->assertStatus(422);
    }
}
