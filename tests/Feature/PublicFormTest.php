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
        $field = $template->fields()->where('type', 'text')->first();
        $this->assertNotNull($field);
        $response = $this->post(route('formulario-publico.submit', $token), [
            '_token' => csrf_token(),
            '_submitter_name' => 'Joao Teste',
            $field->name_key => 'Resposta',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('form_submissions', ['template_id' => $template->id]);
    }
}
