<?php

namespace Tests\Feature\Api;

use App\Models\Clinic;
use App\Models\FormTemplate;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicFormApiShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_includes_form_public_appearance(): void
    {
        $tenant = Tenant::create(['name' => 'Tenant Form', 'slug' => 'tenant-form']);
        $org = Clinic::create([
            'tenant_id' => $tenant->id,
            'name' => 'Clínica Form',
            'slug' => 'clinica-form',
            'notification_email' => 'form@test.com',
            'form_public_theme' => 'ocean-blue',
            'hide_platform_branding' => true,
        ]);

        $template = FormTemplate::withoutGlobalScopes()->create([
            'organization_id' => $org->id,
            'name' => 'Anamnese',
            'is_active' => true,
            'public_enabled' => true,
            'public_token' => 'test-token-abc',
        ]);

        $response = $this->getJson('/api/v1/formulario-publico/'.$template->public_token);

        $response->assertOk()
            ->assertJsonPath('data.form_public_theme', 'ocean-blue')
            ->assertJsonPath('data.hide_platform_branding', true)
            ->assertJsonPath('data.clinic_slug', 'clinica-form');

        $accent = $response->json('data.accent_hex');
        $this->assertIsString($accent);
        $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/', $accent);
    }
}
