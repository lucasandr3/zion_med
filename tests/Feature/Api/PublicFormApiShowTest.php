<?php

namespace Tests\Feature\Api;

use App\Models\FormTemplate;
use Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicFormApiShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_exposes_clinic_appearance_for_public_form(): void
    {
        $this->seed(OrganizationSeeder::class);
        $clinic = \App\Models\Clinic::query()->firstOrFail();
        $clinic->update([
            'public_theme' => 'ocean-blue',
            'form_public_theme' => 'ocean-blue',
            'hide_platform_branding' => true,
        ]);

        $template = FormTemplate::withoutGlobalScopes()->create([
            'organization_id' => $clinic->id,
            'name' => 'Ficha de anamnese',
            'description' => 'Descrição',
            'category' => 'geral',
            'is_active' => true,
            'public_enabled' => true,
            'public_require_person_link' => false,
            'public_token' => str_repeat('a', 32),
        ]);

        $response = $this->getJson('/api/v1/formulario-publico/'.$template->public_token);

        $response->assertOk()
            ->assertJsonPath('data.template.name', 'Ficha de anamnese')
            ->assertJsonPath('data.clinic_slug', $clinic->slug)
            ->assertJsonPath('data.public_theme', 'ocean-blue')
            ->assertJsonPath('data.form_public_theme', 'ocean-blue')
            ->assertJsonPath('data.accent_hex', '#2563eb')
            ->assertJsonPath('data.hide_platform_branding', true);
    }
}
