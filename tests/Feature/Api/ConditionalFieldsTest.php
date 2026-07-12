<?php

namespace Tests\Feature\Api;

use App\Models\FormField;
use App\Models\FormTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConditionalFieldsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\OrganizationSeeder::class);
    }

    private function createPublicTemplate(User $user, array $overrides = []): FormTemplate
    {
        return FormTemplate::withoutGlobalScopes()->create(array_merge([
            'organization_id' => $user->organization_id,
            'name' => 'Form Condicional',
            'description' => null,
            'category' => 'consentimento',
            'document_kind' => 'consentimento',
            'is_active' => true,
            'public_enabled' => true,
            'public_require_person_link' => false,
            'public_token' => str_repeat('f', 32),
        ], $overrides));
    }

    public function test_show_includes_visibility_rules(): void
    {
        $user = $this->qaClinicOwnerUser();
        $template = $this->createPublicTemplate($user);

        FormField::create([
            'template_id' => $template->id,
            'type' => 'radio',
            'label' => 'É menor de idade?',
            'name_key' => 'eh_menor',
            'required' => true,
            'options_json' => ['options' => ['Sim', 'Não']],
            'sort_order' => 1,
        ]);

        FormField::create([
            'template_id' => $template->id,
            'type' => 'text',
            'label' => 'Nome do responsável',
            'name_key' => 'nome_responsavel',
            'required' => true,
            'visibility_rules' => [
                'show_when' => [
                    ['field' => 'eh_menor', 'operator' => 'equals', 'value' => 'Sim'],
                ],
            ],
            'sort_order' => 2,
        ]);

        $response = $this->getJson('/api/v1/formulario-publico/'.$template->public_token);

        $response->assertOk()
            ->assertJsonPath('data.fields.1.visibility_rules.show_when.0.field', 'eh_menor');
    }

    public function test_submit_skips_hidden_required_field(): void
    {
        $user = $this->qaClinicOwnerUser();
        $template = $this->createPublicTemplate($user);

        FormField::create([
            'template_id' => $template->id,
            'type' => 'radio',
            'label' => 'É menor de idade?',
            'name_key' => 'eh_menor',
            'required' => true,
            'options_json' => ['options' => ['Sim', 'Não']],
            'sort_order' => 1,
        ]);

        FormField::create([
            'template_id' => $template->id,
            'type' => 'text',
            'label' => 'Nome do responsável',
            'name_key' => 'nome_responsavel',
            'required' => true,
            'visibility_rules' => [
                'show_when' => [
                    ['field' => 'eh_menor', 'operator' => 'equals', 'value' => 'Sim'],
                ],
            ],
            'sort_order' => 2,
        ]);

        $token = $template->public_token;

        $this->postJson("/api/v1/formulario-publico/{$token}/submit", [
            '_submitter_name' => 'Adulto',
            '_comprehension_ack' => true,
            'eh_menor' => 'Não',
        ])->assertCreated();

        $this->postJson("/api/v1/formulario-publico/{$token}/submit", [
            '_submitter_name' => 'Menor sem responsável',
            '_comprehension_ack' => true,
            'eh_menor' => 'Sim',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['nome_responsavel']);

        $this->postJson("/api/v1/formulario-publico/{$token}/submit", [
            '_submitter_name' => 'Menor com responsável',
            '_comprehension_ack' => true,
            'eh_menor' => 'Sim',
            'nome_responsavel' => 'Maria Silva',
        ])->assertCreated();
    }

    public function test_submit_requires_guardian_when_actors_rules_match(): void
    {
        $user = $this->qaClinicOwnerUser();
        $template = $this->createPublicTemplate($user, [
            'actors_visibility_rules' => [
                'show_when' => [
                    ['field' => 'eh_menor', 'operator' => 'equals', 'value' => 'Sim'],
                ],
                'require_guardian' => true,
            ],
        ]);

        FormField::create([
            'template_id' => $template->id,
            'type' => 'radio',
            'label' => 'É menor de idade?',
            'name_key' => 'eh_menor',
            'required' => true,
            'options_json' => ['options' => ['Sim', 'Não']],
            'sort_order' => 1,
        ]);

        $token = $template->public_token;

        $this->postJson("/api/v1/formulario-publico/{$token}/submit", [
            '_submitter_name' => 'Menor',
            '_comprehension_ack' => true,
            'eh_menor' => 'Sim',
        ])->assertStatus(422);

        $this->postJson("/api/v1/formulario-publico/{$token}/submit", [
            '_submitter_name' => 'Menor',
            '_comprehension_ack' => true,
            'eh_menor' => 'Sim',
            '_actors' => [
                'guardian_name' => 'Ana Responsável',
                'guardian_relation' => 'mãe',
            ],
        ])->assertCreated();
    }
}
