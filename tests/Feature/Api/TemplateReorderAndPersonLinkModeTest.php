<?php

namespace Tests\Feature\Api;

use App\Models\FormField;
use App\Models\FormTemplate;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TemplateReorderAndPersonLinkModeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\OrganizationSeeder::class);
        $this->seed(\Database\Seeders\FormTemplateSeeder::class);
    }

    public function test_reorder_campos_updates_sort_order(): void
    {
        $user = $this->qaClinicOwnerUser();
        Sanctum::actingAs($user);
        session(['current_clinic_id' => $user->organization_id]);

        $template = FormTemplate::withoutGlobalScopes()
            ->where('organization_id', $user->organization_id)
            ->firstOrFail();

        $a = FormField::create([
            'template_id' => $template->id,
            'type' => 'text',
            'label' => 'A',
            'name_key' => 'campo_a_'.uniqid(),
            'required' => false,
            'sort_order' => 1,
        ]);
        $b = FormField::create([
            'template_id' => $template->id,
            'type' => 'text',
            'label' => 'B',
            'name_key' => 'campo_b_'.uniqid(),
            'required' => false,
            'sort_order' => 2,
        ]);

        $response = $this->postJson("/api/v1/templates/{$template->id}/campos/reorder", [
            'ids' => [$b->id, $a->id],
        ]);

        $response->assertOk();
        $this->assertSame(1, $b->fresh()->sort_order);
        $this->assertSame(2, $a->fresh()->sort_order);
    }

    public function test_public_person_link_mode_is_persisted_and_exposed(): void
    {
        $user = $this->qaClinicOwnerUser();
        Sanctum::actingAs($user);
        session(['current_clinic_id' => $user->organization_id]);

        $template = FormTemplate::withoutGlobalScopes()
            ->where('organization_id', $user->organization_id)
            ->firstOrFail();

        $this->putJson("/api/v1/templates/{$template->id}", [
            'name' => $template->name,
            'public_require_person_link' => true,
            'public_person_link_mode' => 'cpf',
        ])->assertOk()
            ->assertJsonPath('data.public_person_link_mode', 'cpf');

        $template->refresh();
        $this->assertSame('cpf', $template->public_person_link_mode);

        $this->putJson("/api/v1/templates/{$template->id}", [
            'name' => $template->name,
            'public_require_person_link' => true,
            'public_person_link_mode' => 'code',
        ])->assertOk()
            ->assertJsonPath('data.public_person_link_mode', 'code');
    }

    public function test_validate_person_respects_code_mode(): void
    {
        $template = FormTemplate::withoutGlobalScopes()->firstOrFail();
        $template->update([
            'public_enabled' => true,
            'is_active' => true,
            'public_token' => 'tok-code-mode',
            'public_require_person_link' => true,
            'public_person_link_mode' => 'code',
        ]);

        Person::withoutGlobalScopes()->create([
            'organization_id' => $template->organization_id,
            'code' => 'PAC001',
            'name' => 'Paciente Code',
            'birth_date' => '1990-05-10',
            'status' => 'active',
        ]);

        $this->postJson('/api/v1/formulario-publico/tok-code-mode/validate-person', [
            'cpf' => '529.982.247-25',
        ])->assertStatus(422);

        $this->postJson('/api/v1/formulario-publico/tok-code-mode/validate-person', [
            'code' => 'PAC001',
            'birth_date' => '1990-05-10',
        ])->assertOk()
            ->assertJsonPath('data.name', 'Paciente Code');
    }
}
