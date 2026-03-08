<?php

namespace Tests\Feature;

use App\Models\FormTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_form_page_loads_with_valid_token(): void
    {
        $this->seed(\Database\Seeders\ClinicSeeder::class);
        $this->seed(\Database\Seeders\FormTemplateSeeder::class);
        $template = FormTemplate::withoutGlobalScopes()->first();
        $token = str_repeat('a', 32);
        $template->update(['public_enabled' => true, 'public_token' => $token]);
        $response = $this->get(route('formulario-publico.show', ['token' => $token]));
        $response->assertStatus(200);
        $response->assertSee($template->name);
    }

    public function test_public_form_submission_creates_submission(): void
    {
        $this->seed(\Database\Seeders\ClinicSeeder::class);
        $this->seed(\Database\Seeders\FormTemplateSeeder::class);
        $template = FormTemplate::withoutGlobalScopes()->first();
        $token = str_repeat('b', 32);
        $template->update(['public_enabled' => true, 'public_token' => $token]);
        $template->load('fields');
        $payload = [
            '_token' => csrf_token(),
            '_submitter_name' => 'Joao Teste',
        ];
        foreach ($template->fields as $field) {
            if ($field->type === 'file' || $field->type === 'signature') {
                continue;
            }
            if ($field->required) {
                $payload[$field->name_key] = match ($field->type) {
                    'date' => '2025-01-15',
                    'number' => 1,
                    'textarea' => 'Resposta texto',
                    'select', 'radio' => null,
                    'checkbox' => '1',
                    default => 'Resposta',
                };
                if (in_array($field->type, ['select', 'radio'], true) && $field->options_json) {
                    $opts = $field->options_json['options'] ?? [];
                    $payload[$field->name_key] = is_array($opts) ? ($opts[0] ?? 'Sim') : 'Sim';
                }
            }
        }
        $response = $this->post(route('formulario-publico.submit', $token), $payload);
        $response->assertRedirect();
        $this->assertDatabaseHas('form_submissions', ['template_id' => $template->id]);
    }
}
